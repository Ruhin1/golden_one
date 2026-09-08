<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_type',
        'quantity',
        'sold_weight_in_grams',
        'applied_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sold_weight_in_grams' => 'integer',
        'applied_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // আইটেমটি একটি নির্দিষ্ট সেলের (ইনভয়েসের) অংশ
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // আইটেমটি একটি নির্দিষ্ট প্রোডাক্ট
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}