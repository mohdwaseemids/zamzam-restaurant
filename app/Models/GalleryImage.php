<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $guarded = [];

    protected $casts = ['is_featured' => 'boolean', 'is_active' => 'boolean'];

    protected $appends = ['image_url'];

    public const CATEGORIES = [
        'food' => 'Food',
        'ambience' => 'Ambience',
        'events' => 'Events & Parties',
        'kitchen' => 'Kitchen',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->latest('id');
    }

    public function getImageUrlAttribute(): string
    {
        return media_url($this->image, $this->title ?: 'Gallery');
    }
}
