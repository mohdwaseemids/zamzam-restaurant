<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['spent_on' => 'date', 'amount' => 'decimal:2'];

    public const CATEGORIES = [
        'meat' => 'Meat & fish',
        'grocery' => 'Grocery & vegetables',
        'gas' => 'Gas & charcoal',
        'salary' => 'Staff salary',
        'rent' => 'Rent',
        'electricity' => 'Electricity & water',
        'packaging' => 'Packaging',
        'maintenance' => 'Repairs & maintenance',
        'marketing' => 'Marketing',
        'other' => 'Other',
    ];

    public const PAID_VIA = ['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank transfer', 'card' => 'Card', 'credit' => 'On credit'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
