<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_public_pages_load(): void
    {
        foreach (['/menu', '/gallery', '/stories', '/reserve', '/contact', '/track', '/login', '/register'] as $page) {
            $this->get($page)->assertStatus(200);
        }
    }

    public function test_guest_can_place_a_cash_order(): void
    {
        $dish = MenuItem::where('price', '>=', 300)->firstOrFail();

        $this->postJson('/cart/add', ['item_id' => $dish->id, 'quantity' => 2])->assertOk()->assertJsonPath('count', 2);

        $this->post('/checkout', [
            'customer_name' => 'Test Guest',
            'phone' => '9876543210',
            'type' => 'pickup',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame((float) $dish->final_price * 2, (float) $order->total);
        $this->get('/order/'.$order->token)->assertOk()->assertSee($order->order_number);
    }

    public function test_customer_can_register_and_cannot_open_admin(): void
    {
        $this->post('/register', [
            'name' => 'Test Customer',
            'phone' => '+91 98123 45678',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/account');

        $customer = User::where('phone', '9812345678')->firstOrFail();
        $this->assertTrue($customer->hasRole('customer'));

        $this->get('/account')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_every_admin_page_loads_for_the_owner(): void
    {
        $this->actingAs(User::where('email', env('ADMIN_EMAIL', 'admin@zamzam.test'))->firstOrFail());

        $pages = [
            '', 'orders', 'reservations', 'sales-report', 'restaurant-tables', 'delivery-areas', 'expenses', 'categories', 'menu-items',
            'coupons', 'gallery-images', 'videos', 'instagram-posts', 'customers', 'reviews', 'contact-messages', 'users', 'roles', 'settings',
        ];

        foreach ($pages as $page) {
            $this->get('/admin/'.$page)->assertOk();
        }

        $table = RestaurantTable::factory()->create();
        $this->get(route('admin.tables.qr', $table))->assertOk()->assertSee($table->name);
    }

    public function test_staff_only_see_modules_their_role_allows(): void
    {
        $counter = User::where('email', 'counter@zamzam.test')->firstOrFail();

        $this->actingAs($counter);
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
    }
}
