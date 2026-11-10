<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'photos' => 'array',
        'is_veg' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_available' => 'boolean',
    ];

    protected $appends = ['image_url', 'final_price'];

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item) {
            if (blank($item->slug)) {
                $base = Str::slug($item->name);
                $slug = $base;
                $i = 2;
                while (static::withTrashed()->where('slug', $slug)->where('id', '!=', $item->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $item->slug = $slug;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->has_discount ? $this->sale_price : $this->price);
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price > 0 && (float) $this->sale_price < (float) $this->price;
    }

    public function getImageUrlAttribute(): string
    {
        return media_url($this->image, $this->name);
    }
}
