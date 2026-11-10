<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
    }

    public function boot(): void
    {
        // Super admin passes every permission check.
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);

        // Admin uploads: allow videos up to 200 MB through the upload widget.
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:204800']]);

        // Serve uploads from whatever host the site is opened on (artisan serve, WAMP, live domain).
        if (! $this->app->runningInConsole()) {
            config(['filesystems.disks.public.url' => rtrim(request()->root(), '/').'/storage']);
        }

        View::composer('layouts.site', function ($view) {
            $view->with('cartCount', app(CartService::class)->summary()['count']);
        });
    }
}
