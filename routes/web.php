<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\SiteController;
use App\Models\Order;
use App\Models\RestaurantTable;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/menu', [SiteController::class, 'menu'])->name('menu');
Route::get('/gallery', [SiteController::class, 'gallery'])->name('gallery');
Route::get('/stories', [SiteController::class, 'stories'])->name('stories');
Route::post('/reviews', [SiteController::class, 'storeReview'])->middleware('throttle:5,10,w1')->name('reviews.store');
Route::get('/reserve', [SiteController::class, 'reserve'])->name('reserve');
Route::post('/reserve', [SiteController::class, 'storeReservation'])->middleware('throttle:6,10,w2')->name('reserve.store');
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::post('/contact', [SiteController::class, 'storeContact'])->middleware('throttle:6,10,w3')->name('contact.store');
Route::get('/placeholder.svg', [SiteController::class, 'placeholder'])->name('placeholder');
Route::get('/menu/{menuItem:slug}', [SiteController::class, 'dish'])->name('dish');
Route::post('/favourites/{menuItem}', [SiteController::class, 'toggleFavourite'])->middleware('auth')->name('favourites.toggle');
Route::get('/t/{token}', [SiteController::class, 'tableScan'])->name('table.scan');
Route::post('/leave-table', [SiteController::class, 'leaveTable'])->name('table.leave');
Route::get('/{page}', [SiteController::class, 'legal'])->whereIn('page', ['privacy-policy', 'terms', 'refund-policy'])->name('legal');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::post('/update', [CartController::class, 'update'])->name('update');
    Route::post('/coupon', [CartController::class, 'coupon'])->middleware('throttle:20,10,w4')->name('coupon');
    Route::post('/options', [CartController::class, 'options'])->name('options');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
});

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,10,w5')->name('checkout.store');
Route::get('/order/{token}', [CheckoutController::class, 'order'])->name('order.show');
Route::get('/order/{token}/pay', [CheckoutController::class, 'pay'])->name('order.pay');
Route::post('/order/{token}/verify', [CheckoutController::class, 'verify'])->name('order.verify');
Route::get('/track', [CheckoutController::class, 'track'])->name('track');
Route::post('/track', [CheckoutController::class, 'findOrder'])->middleware('throttle:8,10,w6')->name('track.find');

// Called by Razorpay servers (CSRF exempt in bootstrap/app.php, protected by signature).
Route::post('/razorpay/webhook', [CheckoutController::class, 'webhook'])->name('razorpay.webhook');

// ---------- Customer accounts ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,5,w7')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,10,w8')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:4,10,w9')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:6,10,w10')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('account');
    Route::get('/orders', [AccountController::class, 'orderHistory'])->name('account.orders');
    Route::post('/orders/{order}/reorder', [AccountController::class, 'reorder'])->name('account.reorder');
    Route::get('/favourites', [AccountController::class, 'favourites'])->name('account.favourites');
    Route::get('/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::put('/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,10,w11')->name('account.password');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::put('/addresses/{address}/default', [AccountController::class, 'makeDefaultAddress'])->name('account.addresses.default');
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
});

// Printable bill for staff (opened from Admin → Orders).
Route::get('/admin/orders/{order}/bill', function (Order $order) {
    abort_unless(auth()->check() && auth()->user()->can('view orders'), 403);

    return view('admin.bill', ['order' => $order->load('items')]);
})->name('admin.orders.bill');

// Printable QR code for a table (Admin → Tables & QR codes).
Route::get('/admin/tables/{table}/qr', function (RestaurantTable $table) {
    abort_unless(auth()->check() && auth()->user()->can('view tables'), 403);

    return view('admin.table-qr', [
        'table' => $table,
        'qr' => (new QRCode(new QROptions(['scale' => 12, 'quietzoneSize' => 2])))->render($table->order_url),
    ]);
})->name('admin.tables.qr');

// GST tax invoice (Admin → Orders → Tax invoice).
Route::get('/admin/orders/{order}/invoice', function (Order $order) {
    abort_unless(auth()->check() && auth()->user()->can('view orders'), 403);
    abort_if($order->status === 'cancelled', 404);

    $order->ensureInvoiceNumber();

    return view('admin.invoice', ['order' => $order->load('items', 'table')]);
})->name('admin.orders.invoice');
