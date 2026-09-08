<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\CustomerLedgerService;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $customers = Customer::latest()->get();
            return response()->json(['data' => $customers]);
        }

        return view('admin.customers.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20|unique:customers,phone',
            'address'     => 'nullable|string|max:500',
            'opening_due' => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $openingDue = $request->opening_due ?? 0.00;

        // নতুন কাস্টমারের এখনো কোনো sale/payment নেই, তাই সরাসরি বসালেও কোনো
        // অসামঞ্জস্য তৈরি হয় না — শুধু সামঞ্জস্যের জন্য opening_due_paid স্পষ্টভাবে ০ রাখা হলো
        $customer = Customer::create([
            'name'             => $request->name,
            'phone'            => $request->phone,
            'address'          => $request->address,
            'opening_due'      => $openingDue,
            'opening_due_paid' => 0.00,
            'current_due'      => $openingDue, // শুরুতে বর্তমান বকেয়া এবং ওপেনিং বকেয়া সমান থাকবে
            'is_active'        => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'কাস্টমার সফলভাবে যুক্ত করা হয়েছে!',
            'data'    => $customer
        ]);
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json(['data' => $customer]);
    }

    /**
     * ⚠️ আগের বাগ: opening_due বদলালে current_due-তে সরাসরি পার্থক্য
     * (dueDifference) যোগ/বিয়োগ করা হতো — এটা opening_due_paid-এর কথা মাথায়
     * রাখত না, ফলে opening_due কমানো হলে (বিশেষ করে তার কিছুটা আগে থেকেই
     * শোধ হয়ে থাকলে) current_due সাময়িকভাবে ভুল/অসামঞ্জস্যপূর্ণ থেকে যেতে পারত।
     *
     * ✅ ফিক্স: current_due এখানে ম্যানুয়ালি ছোঁয়া হচ্ছে না। শুধু opening_due
     * আপডেট করে CustomerLedgerService::recalculate() কল করা হচ্ছে — এটাই
     * opening_due_paid-কে নতুন opening_due-এর সাথে সামঞ্জস্যপূর্ণভাবে পুনরায়
     * ক্ল্যাম্প করে এবং current_due সঠিকভাবে বসিয়ে দেয়।
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20|unique:customers,phone,' . $id,
            'address'     => 'nullable|string|max:500',
            'opening_due' => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $newOpeningDue = $request->opening_due ?? 0.00;

        $customer->update([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'address'     => $request->address,
            'opening_due' => $newOpeningDue,
            'is_active'   => $request->has('is_active') ? 1 : 0,
            // current_due ইচ্ছাকৃতভাবে এখানে বসানো হচ্ছে না
        ]);

        // opening_due-এর পরিবর্তনের প্রভাব (ও opening_due_paid-এর সাথে সামঞ্জস্য)
        // সঠিকভাবে ধরার জন্য পুরো লেজার রিক্যালকুলেট করা হলো — এটাই একমাত্র
        // নির্ভরযোগ্য পথ, ম্যানুয়াল যোগ/বিয়োগ নয়।
        $customer = app(CustomerLedgerService::class)->recalculate($customer->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'কাস্টমার তথ্য আপডেট হয়েছে!',
            'data'    => $customer
        ]);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        // বকেয়া থাকা অবস্থায় কাস্টমার ডিলিট হয়ে গেলে সেই বকেয়ার হিসাব
        // কোথাও ট্র্যাক করা যাবে না (sale.customer_id null হয়ে যাবে) — তাই
        // ভুলবশত ডাটা হারানো ঠেকাতে একটা সতর্কতামূলক চেক যোগ করা হলো।
        if ((float) $customer->current_due > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'এই কাস্টমারের ৳' . number_format($customer->current_due, 2) . ' বকেয়া এখনো অপরিশোধিত আছে — বকেয়া শূন্য না হওয়া পর্যন্ত ডিলিট করা যাবে না।'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'কাস্টমার ডিলিট করা হয়েছে!'
        ]);
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->is_active = !$customer->is_active;
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'স্ট্যাটাস আপডেট করা হয়েছে!'
        ]);
    }
}