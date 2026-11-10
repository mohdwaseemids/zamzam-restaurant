<?php

namespace App\Services;

use App\Models\InstagramPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pulls the restaurant's own posts through the official Instagram API
 * (Instagram API with Instagram Login — needs a long-lived access token
 * of the restaurant's professional account, saved in Website settings).
 */
class InstagramService
{
    protected const ENDPOINT = 'https://graph.instagram.com/me/media';

    public function isConfigured(): bool
    {
        return filled(setting('instagram_access_token'));
    }

    /** @return int number of posts created or updated */
    public function sync(int $limit = 18): int
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Add the Instagram access token in Website settings → Instagram & social first.');
        }

        $response = Http::timeout(20)->get(self::ENDPOINT, [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => $limit,
            'access_token' => setting('instagram_access_token'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?: 'Instagram did not accept the request (HTTP '.$response->status().').');
        }

        $count = 0;

        foreach ($response->json('data', []) as $media) {
            $source = $media['media_type'] === 'VIDEO' ? ($media['thumbnail_url'] ?? null) : ($media['media_url'] ?? null);

            if (! $source) {
                continue;
            }

            $post = InstagramPost::firstOrNew(['instagram_id' => $media['id']]);

            // Instagram CDN links expire, so keep our own copy of the picture.
            if (! $post->exists || blank($post->image)) {
                $image = Http::timeout(30)->get($source);

                if ($image->failed()) {
                    continue;
                }

                $path = 'instagram/'.$media['id'].'.jpg';
                Storage::disk('public')->put($path, $image->body());
                $post->image = $path;
            }

            $post->fill([
                'media_type' => $media['media_type'],
                'caption' => $media['caption'] ?? null,
                'permalink' => $media['permalink'] ?? null,
                'posted_at' => isset($media['timestamp']) ? Carbon::parse($media['timestamp']) : null,
            ])->save();

            $count++;
        }

        return $count;
    }
}
