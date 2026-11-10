<?php

use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\GalleryImage;
use App\Models\MenuItem;
use App\Models\Review;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Read-only API for the future mobile app (v1).
| Ordering / login endpoints will be added here with Sanctum tokens.
*/
Route::prefix('v1')->middleware('throttle:120,1,a1')->group(function () {
    Route::get('/info', fn () => [
        'name' => setting('site_name', config('app.name')),
        'tagline' => setting('tagline'),
        'phone' => setting('phone'),
        'whatsapp' => setting('whatsapp'),
        'address' => setting('address'),
        'opening_hours' => setting('opening_hours'),
        'ordering_enabled' => (bool) setting('ordering_enabled', true),
        'delivery_enabled' => (bool) setting('delivery_enabled', true),
        'min_order' => (float) setting('min_order', 0),
        'delivery_charge' => (float) setting('delivery_charge', 0),
        'free_delivery_above' => (float) setting('free_delivery_above', 0),
        'instagram_url' => setting('instagram_url'),
    ]);

    Route::get('/categories', fn () => Category::active()->get(['id', 'name', 'slug', 'tagline', 'description', 'image']));

    Route::get('/menu', fn (Request $request) => MenuItem::available()
        ->whereHas('category', fn ($q) => $q->where('is_active', true))
        ->when($request->query('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
        ->when($request->query('search'), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
        ->with('category:id,name,slug')
        ->orderBy('sort_order')
        ->paginate(50));

    Route::get('/menu/{menuItem}', fn (MenuItem $menuItem) => $menuItem->load('category:id,name,slug'));

    Route::get('/gallery', fn () => GalleryImage::active()->get(['id', 'title', 'image', 'category']));

    Route::get('/videos', fn () => Video::active()->get());

    Route::get('/reviews', fn () => Review::approved()->latest()->paginate(20, ['id', 'customer_name', 'city', 'rating', 'comment', 'photo', 'created_at']));

    // Push notification scope: the app registers its FCM / APNs token here.
    Route::post('/device-tokens', function (Request $request) {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:android,ios,web'],
        ]);

        DeviceToken::updateOrCreate(['token' => $data['token']], ['platform' => $data['platform'], 'last_used_at' => now()]);

        return response()->json(['ok' => true], 201);
    })->middleware('throttle:10,1,a2');
});

/*
| Customer accounts for the mobile app (Sanctum personal access tokens).
| Send the token as:  Authorization: Bearer <token>
*/
Route::prefix('v1/auth')->group(function () {
    Route::post('/register', function (Request $request) {
        $request->merge(['phone' => \App\Models\User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', \Illuminate\Validation\Rules\Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = \App\Models\User::create(\Illuminate\Support\Arr::only($data, ['name', 'phone', 'email', 'password']) + ['is_active' => true]);
        $user->assignRole('customer');

        return response()->json([
            'token' => $user->createToken($data['device_name'] ?? 'app')->plainTextToken,
            'user' => $user->only(['id', 'name', 'phone', 'email']),
        ], 201);
    })->middleware('throttle:6,10,a3');

    Route::post('/login', function (Request $request) {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);

        $user = str_contains($data['login'], '@')
            ? \App\Models\User::where('email', strtolower(trim($data['login'])))->first()
            : \App\Models\User::where('phone', \App\Models\User::normalizePhone($data['login']))->first();

        if (! $user || ! $user->is_active || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'The mobile number / email or password is not correct.'], 422);
        }

        return [
            'token' => $user->createToken($data['device_name'] ?? 'app')->plainTextToken,
            'user' => $user->only(['id', 'name', 'phone', 'email']),
        ];
    })->middleware('throttle:8,5,a4');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'phone', 'email']) + ['addresses' => $request->user()->addresses]);

        Route::get('/orders', fn (Request $request) => $request->user()->orders()->where('source', '!=', 'counter')->with('items')->latest()->paginate(15));

        Route::post('/logout', function (Request $request) {
            $request->user()->currentAccessToken()->delete();

            return ['ok' => true];
        });
    });
});
