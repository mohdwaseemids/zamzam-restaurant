<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    protected $guarded = [];

    protected $casts = ['is_featured' => 'boolean', 'is_active' => 'boolean'];

    protected $appends = ['thumbnail_url', 'embed_url', 'file_url'];

    public const TYPES = [
        'youtube' => 'YouTube link',
        'upload' => 'Uploaded video file',
        'instagram' => 'Instagram reel link',
    ];

    public const CATEGORIES = [
        'review' => 'Customer review',
        'live' => 'Live from restaurant',
        'food' => 'Food & kitchen',
        'ambience' => 'Ambience',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->latest('id');
    }

    public function getYoutubeIdAttribute(): ?string
    {
        if ($this->type !== 'youtube' || blank($this->url)) {
            return null;
        }

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $this->url, $m)) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{11}$~', $this->url) ? $this->url : null;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->type === 'youtube' && $this->youtube_id) {
            return 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?autoplay=1&rel=0';
        }

        if ($this->type === 'instagram' && filled($this->url)) {
            return rtrim(strtok($this->url, '?'), '/').'/embed';
        }

        return null;
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->type === 'upload' && filled($this->video_file)
            ? Storage::disk('public')->url($this->video_file)
            : null;
    }

    public function getThumbnailUrlAttribute(): string
    {
        if (filled($this->thumbnail)) {
            return media_url($this->thumbnail);
        }

        if ($this->youtube_id) {
            return 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg';
        }

        return media_url(null, $this->title);
    }
}
