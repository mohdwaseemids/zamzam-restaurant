<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    protected $guarded = [];

    protected $casts = ['posted_at' => 'datetime', 'is_active' => 'boolean'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderByDesc('posted_at')->latest('id');
    }

    public function getImageUrlAttribute(): string
    {
        return media_url($this->image, 'Instagram');
    }
}
