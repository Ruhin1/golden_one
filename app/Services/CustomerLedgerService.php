<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\CustomerPayment;
use Illuminate\Support\Facades\DB;

class CustomerLedgerService
{
    /**
     * কাস্টমারের পুরো হিসাব (opening due + প্রতিটা sale-এর paid/due + current_due)
     * শূন্য থেকে নতুন করে গণনা করে বসায় — কোনো increment/decrement নেই,
     * তাই কোনোভাবেই ডাটা মিসম্যাচ হওয়ার সুযোগ নেই।
     *
     * এটাকে idempotent রাখা হয়েছে — বারবার কল করলেও একই ফলাফল আসবে।
     */
    public function recalculate(int $customerId): Customer
    {
        return DB::transaction(function () use ($customerId) {
            $customer = Customer::where('id', $customerId)->lockForUpdate()->firstOrFail();

            // সবচেয়ে পুরনো sale আগে (FIFO) — id দিয়ে টাই-ব্রেক
            $sales = Sale::where('customer_id', $customerId)
                ->orderBy('sale_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // এই কাস্টমারের নামে এখন পর্যন্ত জমা হওয়া সব "বকেয়া পেমেন্ট"-এর যোগফল
            $totalPaymentsPool = (float) CustomerPayment::where('customer_id', $customerId)->sum('amount');
            $remaining = $totalPaymentsPool;

            // ধাপ ১: সবার আগে opening_due (পুরনো/লিগ্যাসি বকেয়া) শোধ হবে
            $openingPaid = min($remaining, (float) $customer->opening_due);
            $remaining -= $openingPaid;

            // ধাপ ২: এরপর প্রতিটা sale, তারিখ অনুসারে পুরনো থেকে নতুন
            foreach ($sales as $sale) {
                $owedBeforePool = max(0, (float) $sale->net_amount - (float) $sale->initial_paid_amount);
                $allocated = min($remaining, $owedBeforePool);
                $remaining -= $allocated;

                $paidAmount = round((float) $sale->initial_paid_amount + $allocated, 2);
                $dueAmount  = round(max(0, (float) $sale->net_amount - $paidAmount), 2);

                $status = 'due';
                if ($dueAmount <= 0.00) {
                    $status = 'paid';
                } elseif ($paidAmount > 0) {
                    $status = 'partial';
                }

                if (
                    (float) $sale->paid_amount !== $paidAmount ||
                    (float) $sale->due_amount !== $dueAmount ||
                    $sale->status !== $status
                ) {
                    $sale->paid_amount = $paidAmount;
                    $sale->due_amount  = $dueAmount;
                    $sale->status      = $status;
                    $sale->save();
                }
            }

            $customer->opening_due_paid = round($openingPaid, 2);
            $customer->current_due = round(
                ((float) $customer->opening_due - $openingPaid) + $sales->sum('due_amount'),
                2
            );
            $customer->save();

            return $customer->fresh();
        });
    }
}