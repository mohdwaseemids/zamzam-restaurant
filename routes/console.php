<?php

use App\Services\InstagramService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('instagram:sync', function (InstagramService $instagram) {
    if (! $instagram->isConfigured()) {
        $this->warn('Instagram access token is not set (Admin → Website settings → Instagram & social).');

        return 0;
    }

    try {
        $this->info('Synced '.$instagram->sync().' Instagram posts.');
    } catch (Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Pull the latest posts from the restaurant Instagram account');

// Needs the Laravel scheduler (php artisan schedule:work, or a cron entry in production).
Schedule::command('instagram:sync')->hourly()->withoutOverlapping();
