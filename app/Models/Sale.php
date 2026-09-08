<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

protected $fillable = [
    'invoice_no', 'customer_id', 'user_id', 'gross_amount',
    'discount_type', 'discount_value', 'discount_amount', 'net_amount',
    'initial_paid_amount', // ⬅️ নতুন
    'paid_amount', 'due_amount', 'status', 'sale_date',
];

protected $casts = [
    'gross_amount' => 'decimal:2',
    'discount_value' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'net_amount' => 'decimal:2',
    'initial_paid_amount' => 'decimal:2', // ⬅️ নতুন
    'paid_amount' => 'decimal:2',
    'due_amount' => 'decimal:2',
    'sale_date' => 'datetime',
];

    // ইনভয়েসটি একটি কাস্টমারের নামে হতে পারে (নগদে বিক্রি হলে null)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ইনভয়েসটি যে ক্যাশিয়ার তৈরি করেছে
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // এই মেমোর ভেতরে একাধিক আইটেম থাকবে
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}