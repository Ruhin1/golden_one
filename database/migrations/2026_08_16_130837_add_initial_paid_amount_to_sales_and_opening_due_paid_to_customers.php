<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // বিক্রির মুহূর্তে POS-এ তাৎক্ষণিক জমা নেওয়া টাকা (এটা কখনো বদলাবে না)
            $table->decimal('initial_paid_amount', 12, 2)->default(0.00)->after('net_amount');
        });

        Schema::table('customers', function (Blueprint $table) {
            // opening_due এর কতটুকু এখন পর্যন্ত শোধ হয়েছে (FIFO হিসেবে)
            $table->decimal('opening_due_paid', 12, 2)->default(0.00)->after('opening_due');
        });

        // ⚠️ বিদ্যমান sales-এর জন্য ব্যাকফিল: এখন পর্যন্ত paid_amount যা ছিল
        // সেটাই initial_paid_amount হিসেবে ধরে নেওয়া হলো (যেহেতু আগে FIFO allocation ছিল না)
        DB::table('sales')->update(['initial_paid_amount' => DB::raw('paid_amount')]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('initial_paid_amount');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('opening_due_paid');
        });
    }
};