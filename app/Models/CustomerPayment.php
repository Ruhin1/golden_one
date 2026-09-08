<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'user_id',
        'receipt_no',
        'amount',
        'payment_method',
        'note',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    // পেমেন্টটি একজন কাস্টমারের
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // পেমেন্টটি যে ক্যাশিয়ার গ্রহণ করেছে
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}