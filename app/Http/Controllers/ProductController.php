<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $products = Product::with('category')->latest()->get();
            return response()->json(['data' => $products]);
        }

        $categories = Category::where('is_active', 1)->orderBy('name', 'asc')->get();
        return view('admin.products.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'price_1kg'     => 'required|numeric|min:0',
            'price_half_kg' => 'required|numeric|min:0',
            'stock_kg'      => 'nullable|numeric|min:0',
            'stock_gram'    => 'nullable|numeric|min:0|max:999',
            'image'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active'     => 'nullable|boolean',
        ]);

        $kg = $request->input('stock_kg', 0) ?? 0;
        $gram = $request->input('stock_gram', 0) ?? 0;
        $stockInGrams = ($kg * 1000) + $gram;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $product = Product::create([
                'category_id'    => $request->category_id,
                'name'           => $request->name,
                'price_1kg'      => $request->price_1kg,
                'price_half_kg'  => $request->price_half_kg,
                'stock_in_grams' => $stockInGrams,
                'image'          => $imagePath,
                'is_active'      => $request->has('is_active') ? 1 : 0,
            ]);

            // প্রাথমিক স্টক ইন হিসেবে মুভমেন্ট রেকর্ড
            if ($stockInGrams > 0) {
                StockMovement::create([
                    'product_id'        => $product->id,
                    'type'              => 'in',
                    'quantity_in_grams' => $stockInGrams,
                    'note'              => 'প্রাথমিক স্টক সংযোজন',
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'প্রোডাক্ট সফলভাবে যুক্ত করা হয়েছে!',
                'data'    => $product
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'সার্ভার সমস্যা: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
        return response()->json(['data' => $product]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'price_1kg'     => 'required|numeric|min:0',
            'price_half_kg' => 'required|numeric|min:0',
            'image'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active'     => 'nullable|boolean',
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'category_id'   => $request->category_id,
            'name'          => $request->name,
            'price_1kg'     => $request->price_1kg,
            'price_half_kg' => $request->price_half_kg,
            'image'         => $imagePath,
            'is_active'     => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'প্রোডাক্ট তথ্য আপডেট হয়েছে!',
            'data'    => $product
        ]);
    }

    // স্টক বৃদ্ধি/হ্রাস অ্যাডজাস্টমেন্ট মেথড
    public function adjustStock(Request $request, $id)
    {
        $request->validate([
            'type'         => 'required|in:in,out',
            'adjust_kg'    => 'nullable|numeric|min:0',
            'adjust_gram'  => 'nullable|numeric|min:0|max:999',
            'note'         => 'nullable|string|max:255',
        ]);

        $kg = $request->input('adjust_kg', 0) ?? 0;
        $gram = $request->input('adjust_gram', 0) ?? 0;
        $adjustGrams = ($kg * 1000) + $gram;

        if ($adjustGrams <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'অনুগ্ৰহ করে অন্তত কেজি বা গ্রামের পরিমাণ দিন!'
            ], 422);
        }

        $product = Product::findOrFail($id);

        if ($request->type === 'out' && $product->stock_in_grams < $adjustGrams) {
            return response()->json([
                'status'  => 'error',
                'message' => 'স্টকে পর্যাপ্ত পরিমাণ পণ্য নেই! বর্তমান স্টক: ' . number_format($product->stock_in_grams / 1000, 2) . ' কেজি'
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->type === 'in') {
                $product->increment('stock_in_grams', $adjustGrams);
            } else {
                $product->decrement('stock_in_grams', $adjustGrams);
            }

            StockMovement::create([
                'product_id'        => $product->id,
                'type'              => $request->type,
                'quantity_in_grams' => $adjustGrams,
                'note'              => $request->note ?? ($request->type === 'in' ? 'স্টক বৃদ্ধি' : 'স্টক হ্রাস'),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'স্টক সফলভাবে আপডেট হয়েছে!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'সার্ভার ত্রুটি ঘটেছে!'], 500);
        }
    }

    // স্টক মুভমেন্ট হিস্ট্রি লোড
    public function getMovements($id)
    {
        $product = Product::findOrFail($id);
        $movements = StockMovement::where('product_id', $id)->latest()->get();

        return response()->json([
            'product_name' => $product->name,
            'movements'    => $movements
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'প্রোডাক্ট ডিলিট করা হয়েছে!'
        ]);
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'স্ট্যাটাস আপডেট করা হয়েছে!'
        ]);
    }
}