<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

protected $fillable = [
    'name', 'phone', 'address', 'opening_due',
    'opening_due_paid', // ⬅️ নতুন
    'current_due', 'is_active',
];

protected $casts = [
    'opening_due' => 'decimal:2',
    'opening_due_paid' => 'decimal:2', // ⬅️ নতুন
    'current_due' => 'decimal:2',
    'is_active' => 'boolean',
];

    // কাস্টমারের একাধিক বিক্রির মেমো থাকতে পারে
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // কাস্টমারের একাধিক বকেয়া জমার পেমেন্ট থাকতে পারে
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}