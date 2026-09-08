<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $categories = Category::withCount('products')->latest()->get();
            return response()->json(['data' => $categories]);
        }

        return view('admin.categories.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'ক্যাটাগরি সফলভাবে তৈরি হয়েছে!',
            'data' => $category
        ]);
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $request->name,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'ক্যাটাগরি আপডেট হয়েছে!',
            'data' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'এই ক্যাটাগরির অধীনে প্রোডাক্ট থাকায় ডিলিট করা সম্ভব নয়!'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'ক্যাটাগরি সফলভাবে মুছে ফেলা হয়েছে!'
        ]);
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'স্ট্যাটাস পরিবর্তন করা হয়েছে!'
        ]);
    }
}