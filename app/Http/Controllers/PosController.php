<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Carbon\Carbon;
use App\Services\CustomerLedgerService;
class PosController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        $products = Product::where('is_active', true)->with('category')->get();
        $customers = Customer::select('id', 'name', 'phone', 'current_due')->latest()->get();

        return view('admin.pos.index', compact('categories', 'products', 'customers'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'address' => 'nullable|string|max:500',
            'opening_due' => 'nullable|numeric|min:0',
        ]);

        $openingDue = $request->opening_due ?? 0;

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'opening_due' => $openingDue,
            'current_due' => $openingDue,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'কাস্টমার সফলভাবে যুক্ত হয়েছে!',
            'customer' => $customer
        ]);
    }


    private function gramsForUnit(string $unitType): int
    {
        return match ($unitType) {
            '5kg'  => 5000,
            '1kg'  => 1000,
            '500g' => 500,
            '250g' => 250,
            '100g' => 100,
            '50g'  => 50,
            default => throw new \InvalidArgumentException("অজানা ইউনিট-টাইপ: {$unitType}"),
        };
    }

    public function storeSale(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_type' => 'required|in:5kg,1kg,500g,250g,100g,50g', // ⬅️ ৬টা সাইজ
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.applied_price' => 'required|numeric|min:0', // ⬅️ এখন সরাসরি সেই প্যাকেটের চূড়ান্ত দাম — গুণ/ভাগ কিছুই না
            'paid_amount' => 'required|numeric|min:0',
            'sale_date' => 'nullable|date',
            'discount_type' => 'nullable|in:flat,percentage',
            'discount_value' => 'nullable|numeric|min:0',
        ], [
            'customer_id.required' => 'অনুগ্ৰহ করে একজন কাস্টমার নির্বাচন করুন।',
            'customer_id.exists' => 'নির্বাচিত কাস্টমারটি সঠিক নয়।',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $grossAmount = 0;
                $cartItemsData = [];

                foreach ($request->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    // ⬇️ আগে ছিল: ($is1kg ? 1000 : 500) * quantity — এখন জেনেরিক ম্যাপিং
                    $weightInGrams = $this->gramsForUnit($item['unit_type']) * $item['quantity'];

                    // applied_price এখন সরাসরি সেই প্যাকেটের (যেমন ১টা ২৫০গ্রাম প্যাকেট)
                    // চূড়ান্ত দাম — quantity দিয়ে গুণ করলেই subtotal, এখানে কোনো
                    // পরিবর্তন লাগেনি কারণ এই লজিকটা সবসময় এভাবেই ছিল
                    $appliedPrice = (float) $item['applied_price'];
                    $subtotal = $appliedPrice * $item['quantity'];

                    if ($product->stock_in_grams < $weightInGrams) {
                        throw new Exception("পণ্য '{$product->name}' এর পর্যাপ্ত স্টক নেই!");
                    }

                    $grossAmount += $subtotal;
                    $cartItemsData[] = [
                        'product' => $product,
                        'unit_type' => $item['unit_type'],
                        'quantity' => $item['quantity'],
                        'sold_weight_in_grams' => $weightInGrams,
                        'applied_price' => $appliedPrice,
                        'subtotal' => $subtotal,
                    ];
                }

                $discountAmount = 0;
                $discountValue = (float) ($request->discount_value ?? 0);
                if ($request->discount_type === 'flat') {
                    $discountAmount = $discountValue;
                } elseif ($request->discount_type === 'percentage') {
                    $discountAmount = ($grossAmount * $discountValue) / 100;
                }

                $netAmount = max(0, $grossAmount - $discountAmount);
                $initialPaid = min($netAmount, (float) $request->paid_amount);

                $sale = Sale::create([
                    'invoice_no' => 'INV-' . strtoupper(Str::random(8)),
                    'customer_id' => $request->customer_id,
                    'user_id' => auth()->id(),
                    'gross_amount' => $grossAmount,
                    'discount_type' => $request->discount_type ?? 'flat',
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'net_amount' => $netAmount,
                    'initial_paid_amount' => $initialPaid,
                    'paid_amount' => $initialPaid,
                    'due_amount' => max(0, $netAmount - $initialPaid),
                    'status' => 'due',
                    'sale_date' => $request->filled('sale_date')
                        ? \Carbon\Carbon::parse($request->sale_date)
                        : now(),
                ]);

                foreach ($cartItemsData as $data) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $data['product']->id,
                        'unit_type' => $data['unit_type'],
                        'quantity' => $data['quantity'],
                        'sold_weight_in_grams' => $data['sold_weight_in_grams'],
                        'applied_price' => $data['applied_price'],
                        'subtotal' => $data['subtotal'],
                    ]);

                    $data['product']->decrement('stock_in_grams', $data['sold_weight_in_grams']);
                }

                app(CustomerLedgerService::class)->recalculate($request->customer_id);

                $sale->refresh()->load('customer', 'items.product');

                return response()->json([
                    'success' => true,
                    'message' => 'বিক্রি সফলভাবে সম্পন্ন হয়েছে!',
                    'sale' => $sale
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function updateSale(Request $request, $id)
    {
        $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.unit_type'     => 'required|in:5kg,1kg,500g,250g,100g,50g', // ⬅️ ৬টা সাইজ
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.applied_price' => 'required|numeric|min:0',
            'discount_type'         => 'required|in:flat,percentage',
            'discount_value'        => 'nullable|numeric|min:0',
            'sale_date'              => 'nullable|date',
        ]);

        try {
            return DB::transaction(function () use ($request, $id) {
                $sale = Sale::with('items')->lockForUpdate()->findOrFail($id);
                $oldCustomerId = $sale->customer_id;

                // ১. পূর্বের স্টকে ফিরিয়ে নেওয়া
                foreach ($sale->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->increment('stock_in_grams', $item->sold_weight_in_grams);
                    }
                }

                // ২. পুরনো আইটেম মুছে ফেলা
                $sale->items()->delete();

                // ৩. নতুন হিসাব-নিকাশ ও আইটেম
                $grossAmount = 0;
                $itemsToInsert = [];

                foreach ($request->items as $item) {
                    // ⬇️ আগে ছিল: ($item['unit_type'] === '1kg') ? 1000 : 500 — এখন জেনেরিক ম্যাপিং
                    $soldWeight = $this->gramsForUnit($item['unit_type']) * $item['quantity'];
                    $subtotal = $item['applied_price'] * $item['quantity'];
                    $grossAmount += $subtotal;

                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (!$product || $product->stock_in_grams < $soldWeight) {
                        $productName = $product ? $product->name : 'পণ্য';
                        $availableKg = $product ? ($product->stock_in_grams / 1000) : 0;
                        throw new \Exception("{$productName} এর পর্যাপ্ত স্টক নেই! বর্তমানে মজুদ আছে: {$availableKg} কেজি");
                    }

                    $product->decrement('stock_in_grams', $soldWeight);

                    $itemsToInsert[] = new SaleItem([
                        'product_id'           => $item['product_id'],
                        'unit_type'            => $item['unit_type'],
                        'quantity'             => $item['quantity'],
                        'sold_weight_in_grams' => $soldWeight,
                        'applied_price'        => $item['applied_price'],
                        'subtotal'             => $subtotal,
                    ]);
                }

                $discountValue = (float) ($request->discount_value ?? 0);
                $discountAmount = $request->discount_type === 'percentage'
                    ? ($grossAmount * $discountValue) / 100
                    : $discountValue;

                $netAmount = max(0, $grossAmount - $discountAmount);

                $sale->update([
                    'customer_id'     => $request->customer_id,
                    'gross_amount'    => $grossAmount,
                    'discount_type'   => $request->discount_type,
                    'discount_value'  => $discountValue,
                    'discount_amount' => $discountAmount,
                    'net_amount'      => $netAmount,
                    'sale_date'       => $request->filled('sale_date')
                        ? \Carbon\Carbon::parse($request->sale_date)
                        : $sale->sale_date,
                ]);

                $sale->items()->saveMany($itemsToInsert);

                app(CustomerLedgerService::class)->recalculate($request->customer_id);
                if ($oldCustomerId && $oldCustomerId != $request->customer_id) {
                    app(CustomerLedgerService::class)->recalculate($oldCustomerId);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'বিক্রির তথ্য সফলভাবে আপডেট হয়েছে!'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }



    public function salesIndex(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'items.product']);

        // ১. সার্চ ফিল্টার (ইনভয়েস বা কাস্টমার ফোন/নাম)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // ২. তারিখ ফিল্টার — এখন সম্পূর্ণ ঐচ্ছিক, ডিফল্ট নেই
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate   = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('sale_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('sale_date', '<=', $endDate);
        }

        // ৩. পেমেন্ট স্ট্যাটাস ফিল্টার (ড্রপডাউন — paid/partial/due)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ৪. কাস্টমার ফিল্টার
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // ৫. বকেয়া-টগল বাটনের কাউন্ট — due_filter প্রয়োগের *আগের* অবস্থা থেকে
        // হিসাব করা হচ্ছে, যাতে বাটনগুলোর পাশে সবসময় সঠিক সংখ্যা দেখা যায়
        // (search/date/status/customer ফিল্টার প্রযোজ্য থাকলে সেগুলোসহ)
        $dueCount  = (clone $query)->where('due_amount', '>', 0)->count();
        $paidCount = (clone $query)->where('due_amount', '<=', 0)->count();
        $allCount  = $dueCount + $paidCount;

        // ৬. বকেয়া টগল ফিল্টার (নতুন) — ডিফল্ট: শুধু বকেয়া থাকা সেল
        $dueFilter = $request->get('due_filter', 'due'); // due | paid | all
        if ($dueFilter === 'due') {
            $query->where('due_amount', '>', 0);
        } elseif ($dueFilter === 'paid') {
            $query->where('due_amount', '<=', 0);
        }
        // 'all' হলে অতিরিক্ত কোনো ফিল্টার নেই

        // ৭. সামারি কার্ডের নিখুঁত ক্যালকুলেশন — এই মুহূর্তে যা যা ফিল্টার
        // প্রয়োগ হয়েছে তার ওপর ভিত্তি করে (একটি একক কোয়েরিতে)
        $summaryQuery = clone $query;
        $summary = $summaryQuery->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(net_amount), 0) as total_sales,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(due_amount), 0) as total_due,
            COALESCE(SUM(discount_amount), 0) as total_discount
        ')->first();

        $sales = $query->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);
        $products  = Product::orderBy('name')->get();

        return view('admin.pos.sales_index', compact(
            'sales', 'summary', 'customers', 'products',
            'startDate', 'endDate', 'dueFilter', 'dueCount', 'paidCount', 'allCount'
        ));
    }

    /**
     * ইনভয়েস বা রসিদের বিস্তারিত তথ্য (JSON)
     */
    public function showSale($id)
    {
        $sale = Sale::with(['customer', 'user', 'items.product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'sale'    => $sale
        ]);
    }

    /**
     * বিক্রি সম্পাদনার (Edit) জন্য ডাটা লোড
     */
    public function getSaleEditData($id)
    {
        try {
            $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
            $products = Product::orderBy('name')->get();
            $customers = Customer::orderBy('name')->get();
            
            // আপনার Blade ফাইলে $categories ব্যবহার করা হয়েছে, তাই এটিও তুলে আনতে হবে
            $categories = Category::orderBy('name')->get(); // নিশ্চিত করুন উপরে Category মডেল ইমপোর্ট করা আছে

            return view('admin.pos.edit', compact('sale', 'products', 'customers', 'categories'));
        } catch (\Exception $e) {
            return redirect()->route('pos.sales.index')->with('error', 'সেল এর তথ্য পাওয়া যায়নি!');
        }
    }


    /**
     * বিক্রি ডিলিট করা (স্টক ফেরত ও কাস্টমার বকেয়া কমবে)
     */
    public function destroySale($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $sale = Sale::with('items')->lockForUpdate()->findOrFail($id);
                $customerId = $sale->customer_id;

                foreach ($sale->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->increment('stock_in_grams', $item->sold_weight_in_grams);
                    }
                }

                $sale->items()->delete();
                $sale->delete();

                if ($customerId) {
                    app(CustomerLedgerService::class)->recalculate($customerId);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'বিক্রি সফলভাবে ডিলিট হয়েছে এবং স্টক ও বকেয়া এডজাস্ট করা হয়েছে!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ডিলিট করতে ত্রুটি ঘটেছে: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showInvoice($id)
    {
        $sale = Sale::with(['customer', 'user', 'items.product'])->findOrFail($id);
        $settings = Setting::first();

        // এই ইনভয়েস বাদে কাস্টমারের অন্য যেসব ইনভয়েসে এখনো বকেয়া আছে
        $previousDueCount = 0;
        $previousDue = 0.00;

        // কাস্টমারের opening_due-এর যেটুকু এখনো শোধ হয়নি (আলাদা লাইনে দেখানোর জন্য)
        $openingDueRemaining = 0.00;

        // সবকিছুর যোগফল — সরাসরি Customer.current_due থেকে (একমাত্র নির্ভরযোগ্য উৎস)
        $totalDue = 0.00;

        if ($sale->customer) {
            $previousDueSales = Sale::where('customer_id', $sale->customer_id)
                ->where('id', '!=', $sale->id)
                ->where('due_amount', '>', 0)
                ->get(['due_amount']);

            $previousDueCount = $previousDueSales->count();
            $previousDue = (float) $previousDueSales->sum('due_amount');

            $openingDueRemaining = max(0, (float) $sale->customer->opening_due - (float) $sale->customer->opening_due_paid);

            $totalDue = max(0, (float) $sale->customer->current_due);
        } else {
            // ক্যাশ/ওয়াক-ইন কাস্টমার — কোনো বকেয়ার হিসাব প্রযোজ্য না
            $totalDue = (float) $sale->due_amount;
        }

        $isEmbed = request()->boolean('embed');

        return view('admin.pos.invoice', compact(
            'sale', 'settings', 'previousDue', 'previousDueCount', 'openingDueRemaining', 'totalDue', 'isEmbed'
        ));
    }
}