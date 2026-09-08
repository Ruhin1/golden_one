<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * পেজ ভিউ এবং AJAX ডাটা লোড
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $users = User::when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

            return response()->json($users);
        }

        return view('admin.users.index');
    }

    /**
     * নতুন ইউজার সেভ (AJAX)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'নতুন ইউজার সফলভাবে তৈরি হয়েছে!',
            'user'    => $user
        ]);
    }

    /**
     * ইউজার তথ্য আপডেট (AJAX)
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'ইউজারের তথ্য সফলভাবে আপডেট হয়েছে!',
            'user'    => $user
        ]);
    }

    /**
     * ইউজার ডিলিট (AJAX)
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'আপনি নিজের অ্যাকাউন্ট ডিলিট করতে পারবেন না!'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'ইউজার সফলভাবে ডিলিট হয়েছে!'
        ]);
    }
}