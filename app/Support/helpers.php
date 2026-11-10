<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /** Read a site setting (cached). */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /** Resolve an uploaded file path or external URL into a browser URL. */
    function media_url(?string $path, ?string $label = 'Zam Zam'): string
    {
        if (blank($path)) {
            return route('placeholder', ['label' => $label ?: 'Zam Zam']);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return '₹'.number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
    }
}
