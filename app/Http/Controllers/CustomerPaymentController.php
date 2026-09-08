<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\CustomerLedgerService;

class CustomerPaymentController extends Controller
{
    // ১. নতুন পেমেন্ট গ্রহণ ও বর্তমান বকেয়া আপডেট
    public function store(Request $request)
    {
        $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:50',
            'payment_date'   => 'required|date',
            'note'           => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $customer = Customer::where('id', $request->customer_id)->lockForUpdate()->firstOrFail();

                if ($request->amount > $customer->current_due) {
                    throw new \Exception('পেমেন্টের পরিমাণ বর্তমান বকেয়া (৳' . number_format($customer->current_due, 2) . ') এর চেয়ে বেশি হতে পারবে না।');
                }

                do {
                    $receiptNo = 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(6));
                } while (CustomerPayment::where('receipt_no', $receiptNo)->exists());

                CustomerPayment::create([
                    'customer_id'    => $customer->id,
                    'user_id'        => Auth::id(),
                    'receipt_no'     => $receiptNo,
                    'amount'         => $request->amount,
                    'payment_method' => $request->payment_method,
                    'note'           => $request->note,
                    'payment_date'   => $request->payment_date,
                ]);

                // পুরো লেজার (Customer.current_due + প্রতিটা Sale-এর paid/due) FIFO অনুসারে রিক্যালকুলেট
                app(CustomerLedgerService::class)->recalculate($customer->id);
            });

            return redirect()->back()->with('success', 'বকেয়া পেমেন্ট সফলভাবে গ্রহণ করা হয়েছে এবং সংশ্লিষ্ট সব ইনভয়েস স্বয়ংক্রিয়ভাবে আপডেট হয়েছে।');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ২. আপডেট করা statement মেথড (ওয়েব ভিউ এর জন্য)
    public function statement(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $filterType = $request->get('type', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $ledgerData = $this->getLedgerData($customer, $filterType, $fromDate, $toDate);

        return view('admin.customers.statement', [
            'customer'   => $customer,
            'ledger'     => $ledgerData['ledger'],
            'stats'      => $ledgerData['stats'],
            'filterType' => $filterType,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
        ]);
    }

    // ৩. A4 প্রিন্ট ভিউ এর জন্য
    // public function printStatement(Request $request, $id)
    // {
    //     $customer = Customer::findOrFail($id);
    //     $settings = Setting::first();
    //     $filterType = $request->get('type', 'all');
    //     $fromDate = $request->get('from_date');
    //     $toDate = $request->get('to_date');

    //     $ledgerData = $this->getLedgerData($customer, $filterType, $fromDate, $toDate);

    //     return view('admin.customers.statement_print', [
    //         'customer'   => $customer,
    //         'ledger'     => $ledgerData['ledger'],
    //         'stats'      => $ledgerData['stats'],
    //         'settings'   => $settings,
    //         'filterType' => $filterType,
    //         'fromDate'   => $fromDate,
    //         'toDate'     => $toDate,
    //     ]);
    // }

    public function printStatement(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $settings = Setting::first();
        $filterType = $request->get('type', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $isEmbed = $request->boolean('embed'); // ⬅️ নতুন

        $ledgerData = $this->getLedgerData($customer, $filterType, $fromDate, $toDate);

        return view('admin.customers.statement_print', [
            'customer'   => $customer,
            'ledger'     => $ledgerData['ledger'],
            'stats'      => $ledgerData['stats'],
            'settings'   => $settings,
            'filterType' => $filterType,
            'fromDate'   => $fromDate,
            'toDate'     => $toDate,
            'isEmbed'    => $isEmbed, // ⬅️ নতুন
        ]);
    }


    private function getLedgerData(Customer $customer, $filterType, $fromDate, $toDate)
    {
        $customerId = $customer->id;
        $openingDueVal = (float) ($customer->opening_due ?? 0);

        $salesQuery = DB::table('sales')
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'invoice_no as ref_no',
                DB::raw('COALESCE(net_amount, 0) as total_amount'),
                DB::raw('COALESCE(discount_amount, 0) as discount'),
                DB::raw('COALESCE(paid_amount, 0) as instant_paid'),      // তথ্যের জন্য: এই ইনভয়েসে এখন পর্যন্ত মোট জমা (লাইভ মান)
                DB::raw('COALESCE(net_amount, 0) as debit'),               // ✅ স্থির — ইনভয়েসের পূর্ণ মূল্য, পরে বদলায় না
                DB::raw('COALESCE(initial_paid_amount, 0) as credit'),     // ✅ স্থির — শুধু বিক্রির সময়ের তাৎক্ষণিক জমা
                'sale_date as date_time',
                DB::raw("'invoice' as transaction_type"),
                DB::raw("NULL as note")
            );

        $paymentsQuery = DB::table('customer_payments')
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'receipt_no as ref_no',
                DB::raw('0 as total_amount'),
                DB::raw('0 as discount'),
                DB::raw('0 as instant_paid'),
                DB::raw('0 as debit'),
                'amount as credit',
                'payment_date as date_time',
                DB::raw("'payment' as transaction_type"),
                'payment_method as note'
            );

        if ($fromDate && $toDate) {
            $salesQuery->whereBetween('sale_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $paymentsQuery->whereBetween('payment_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        }

        $allTransactions = $salesQuery->unionAll($paymentsQuery)
            ->orderBy('date_time', 'asc')
            ->get();

        $ledger = [];
        $runningBalance = 0;

        if ($openingDueVal > 0) {
            $runningBalance = $openingDueVal;
            $ledger[] = (object) [
                'id' => null,
                'ref_no' => 'OPENING',
                'total_amount' => 0,
                'discount' => 0,
                'instant_paid' => 0,
                'debit' => $openingDueVal,
                'credit' => 0,
                'date_time' => $customer->created_at ?? null,
                'transaction_type' => 'opening_due',
                'note' => 'প্রারম্ভিক বকেয়া',
                'balance' => max(0, $runningBalance), // ডিসপ্লেতে কখনো মাইনাস নয়
            ];
        }

        $totalSales = 0;
        $totalPaid = 0;

        foreach ($allTransactions as $item) {
            $debit = (float) $item->debit;
            $credit = (float) $item->credit;

            $totalSales += $debit;
            $totalPaid += $credit;
            $runningBalance += ($debit - $credit);

            $item->balance = max(0, $runningBalance); // ডিসপ্লে সেফটি-ফ্লোর
            $ledger[] = $item;
        }

        // ফিল্টারিং (শুধু ভিউয়ের জন্য — টোটাল/current_due হিসাবের পরে করা হয়, তাই stats ঠিক থাকে)
        if ($filterType === 'invoice') {
            $ledger = array_values(array_filter($ledger, fn($i) => in_array($i->transaction_type, ['invoice', 'opening_due'])));
        } elseif ($filterType === 'payment') {
            $ledger = array_values(array_filter($ledger, fn($i) => $i->transaction_type === 'payment'));
        }

        return [
            'ledger' => $ledger,
            'stats'  => [
                // এগুলো ফিল্টার করা সময়সীমার (বা পুরো ইতিহাসের) মোট — informational
                'total_sales' => max(0, $totalSales),
                'total_paid'  => max(0, $totalPaid),
                'opening_due' => $openingDueVal,
                // ⚠️ গুরুত্বপূর্ণ ফিক্স: "বর্তমান বকেয়া" কখনো তারিখ-ফিল্টার বা এই
                // ইউনিয়ন-কোয়েরির হিসাবের ওপর নির্ভর করে না — সবসময় সরাসরি
                // Customer.current_due থেকে আসে, যেটা CustomerLedgerService
                // সবসময় সঠিক রাখে। এটাই একমাত্র নির্ভরযোগ্য উৎস।
                'current_due' => max(0, (float) $customer->current_due),
            ]
        ];
    }

    // ৫. বকেয়া কাস্টমার তালিকা
    public function dueCustomers(Request $request)
    {
        $search = $request->input('search');

        $customers = Customer::where('current_due', '>', 0)
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12);

        return view('admin.customers.due_list', compact('customers', 'search'));
    }

    public function openingDueInvoice($id)
    {
        $customer = Customer::findOrFail($id);
        $settings = Setting::first();
        $isEmbed = request()->boolean('embed');

        $remaining = max(0, (float) $customer->opening_due - (float) $customer->opening_due_paid);

        return view('admin.customers.opening_due_invoice', compact('customer', 'settings', 'isEmbed', 'remaining'));
    }


    public function paymentReceipt($id)
    {
        $payment = CustomerPayment::with('customer')->findOrFail($id);
        $settings = Setting::first();
        $isEmbed = request()->boolean('embed');

        return view('admin.customers.payment_receipt', compact('payment', 'settings', 'isEmbed'));
    }
}