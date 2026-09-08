<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'image',
        'name',
        'price_1kg',
        'price_half_kg',
        'stock_in_grams',
        'is_active',
    ];

    protected $casts = [
        'price_1kg' => 'decimal:2',
        'price_half_kg' => 'decimal:2',
        'stock_in_grams' => 'integer',
        'is_active' => 'boolean',
    ]; 

    // পণ্যটি একটি নির্দিষ্ট ক্যাটাগরির অন্তর্ভুক্ত
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // এই পণ্যটি বহুবার বিক্রি হতে পারে
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }
}