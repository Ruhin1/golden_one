<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out'])->comment('in = স্টক বৃদ্ধি, out = স্টক হ্রাস');
            $table->integer('quantity_in_grams');
            $table->string('note')->nullable(); // যেমন: 'নতুন চালান', 'নষ্ট পণ্য' ইত্যাদি
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};