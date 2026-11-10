<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected $casts = ['is_approved' => 'boolean', 'is_featured' => 'boolean'];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return filled($this->photo) ? media_url($this->photo) : null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->customer_name)))
            ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}
