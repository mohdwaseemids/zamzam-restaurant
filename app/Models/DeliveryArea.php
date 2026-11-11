<?php

namespace App\Models;

use Database\Factories\DeliveryAreaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    /** @use HasFactory<DeliveryAreaFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'charge' => 'decimal:2',
        'min_order' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function getLabelAttribute(): string
    {
        return $this->name.($this->pincode ? ' ('.$this->pincode.')' : '');
    }
}
