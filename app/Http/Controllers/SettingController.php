<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first() ?? new Setting(['currency_symbol' => '৳']);
        return view('admin.settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name'        => 'required|string|max:255',
            'phone'               => 'required|string|max:20',
            'email'               => 'nullable|email|max:255',
            'address'             => 'nullable|string',
            'logo'                => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'invoice_footer_note' => 'nullable|string',
            'currency_symbol'     => 'required|string|max:10',
        ]);

        $setting = Setting::first() ?? new Setting();

        $data = $request->only([
            'company_name', 'phone', 'email', 'address',
            'invoice_footer_note', 'currency_symbol'
        ]);

        if ($request->hasFile('logo')) {
            // আগের লোগো ফাইল থাকলে তা ডিলিট করা
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $data['logo'] = $request->file('logo')->store('settings', 'public');
        }

        $setting->fill($data)->save();

        return redirect()->back()->with('success', 'সেটিংস সফলভাবে আপডেট হয়েছে!');
    }
}