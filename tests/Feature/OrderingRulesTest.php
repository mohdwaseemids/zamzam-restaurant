<?php

namespace Tests\Feature;

use App\Models\DeliveryArea;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\User;
use App\Services\StoreHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // A normal lunch hour, so tests do not depend on when they run.
        Carbon::setTestNow(Carbon::parse('2026-11-11 13:00:00'));
    }

    protected function dish(int $minimumPrice = 200): MenuItem
    {
        return MenuItem::where('price', '>=', $minimumPrice)->whereNull('sale_price')->orderBy('price')->firstOrFail();
    }

    protected function customer(int $points = 0): User
    {
        $user = User::factory()->create(['phone' => '9811111111']);
        $user->forceFill(['loyalty_points' => $points])->save();
        $user->assignRole('customer');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function checkout(array $overrides = []): array
    {
        return $overrides + [
            'customer_name' => 'Test Guest',
            'phone' => '9876543210',
            'type' => 'pickup',
            'payment_method' => 'cod',
        ];
    }

    public function test_delivery_needs_a_listed_area_and_uses_its_charge(): void
    {
        $area = DeliveryArea::factory()->create(['charge' => 60]);
        $dish = $this->dish();

        $this->postJson(route('cart.add'), ['item_id' => $dish->id]);

        $this->post(route('checkout.store'), $this->checkout(['type' => 'delivery', 'address' => '12 Test Street']))
            ->assertSessionHas('error', 'Please choose your delivery area.');
        $this->assertSame(0, Order::count());

        $this->post(route('checkout.store'), $this->checkout(['type' => 'delivery', 'address' => '12 Test Street', 'area_id' => $area->id]))
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(60.0, (float) $order->delivery_charge);
        $this->assertSame($area->id, $order->delivery_area_id);
    }

    public function test_orders_are_refused_when_the_restaurant_is_closed(): void
    {
        Setting::putMany(['hours_wed_closed' => '1']);
        $this->postJson(route('cart.add'), ['item_id' => $this->dish()->id]);

        $this->post(route('checkout.store'), $this->checkout())->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->get(route('checkout'))->assertRedirect(route('menu'));
    }

    public function test_opening_hours_can_run_past_midnight(): void
    {
        Setting::putMany(['hours_wed_open' => '18:00', 'hours_wed_close' => '02:00']);
        $hours = app(StoreHours::class);

        $this->assertFalse($hours->isOpen(Carbon::parse('2026-11-11 13:00')));
        $this->assertTrue($hours->isOpen(Carbon::parse('2026-11-11 23:30')));
        $this->assertTrue($hours->isOpen(Carbon::parse('2026-11-12 01:30')));
        $this->assertStringContainsString('today at 6:00 PM', $hours->status(Carbon::parse('2026-11-11 13:00'))['message']);
    }

    public function test_tracked_stock_runs_out_and_comes_back_on_cancel(): void
    {
        $dish = $this->dish();
        $dish->update(['stock_qty' => 2]);

        $this->postJson(route('cart.add'), ['item_id' => $dish->id, 'quantity' => 5])
            ->assertStatus(422)
            ->assertJsonPath('count', 2);

        $this->post(route('checkout.store'), $this->checkout())->assertRedirect();

        $dish->refresh();
        $this->assertSame(0, $dish->stock_qty);
        $this->assertFalse($dish->is_available);

        Order::firstOrFail()->update(['status' => 'cancelled']);

        $dish->refresh();
        $this->assertSame(2, $dish->stock_qty);
        $this->assertTrue($dish->is_available);
    }

    public function test_loyalty_points_are_spent_earned_and_refunded(): void
    {
        $user = $this->customer(points: 100);
        $dish = $this->dish(300);

        $this->actingAs($user);
        $this->postJson(route('cart.add'), ['item_id' => $dish->id]);
        $this->postJson(route('cart.options'), ['use_points' => true])->assertJsonPath('points_used', 100);

        $this->post(route('checkout.store'), $this->checkout())->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(100, $order->points_redeemed);
        $this->assertSame((float) $dish->price - 100, (float) $order->total);
        $this->assertSame(0, $user->fresh()->loyalty_points);

        $order->moveTo('completed');
        $earned = (int) floor(((float) $dish->price - 100) / 100 * 5);
        $this->assertSame($earned, $order->fresh()->points_earned);
        $this->assertSame($earned, $user->fresh()->loyalty_points);

        $order->fresh()->update(['status' => 'cancelled']);
        $this->assertSame(100, $user->fresh()->loyalty_points);
    }

    public function test_scanning_a_table_code_places_a_dine_in_order(): void
    {
        $table = RestaurantTable::factory()->create(['name' => 'Table 7']);

        $this->get(route('table.scan', $table->token))->assertRedirect(route('menu'));
        $this->postJson(route('cart.add'), ['item_id' => $this->dish()->id]);

        $this->post(route('checkout.store'), $this->checkout(['type' => 'dine_in']))->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('dine_in', $order->type);
        $this->assertSame($table->id, $order->restaurant_table_id);
    }

    public function test_dine_in_is_not_accepted_without_scanning_a_table(): void
    {
        $this->postJson(route('cart.add'), ['item_id' => $this->dish()->id]);

        $this->post(route('checkout.store'), $this->checkout(['type' => 'dine_in']))->assertSessionHasErrors('type');
    }

    public function test_dish_page_favourites_and_legal_pages(): void
    {
        $dish = $this->dish();

        $this->get(route('dish', $dish))->assertOk()->assertSee($dish->name);
        $this->postJson(route('favourites.toggle', $dish))->assertUnauthorized();

        foreach (['privacy-policy', 'terms', 'refund-policy'] as $page) {
            $this->get(route('legal', $page))->assertOk();
        }

        $this->actingAs($this->customer());
        $this->postJson(route('favourites.toggle', $dish))->assertOk()->assertJsonPath('saved', true);
        $this->get(route('account.favourites'))->assertOk()->assertSee($dish->name);
        $this->postJson(route('favourites.toggle', $dish))->assertJsonPath('saved', false);
    }

    public function test_tax_invoices_are_numbered_in_sequence(): void
    {
        $this->postJson(route('cart.add'), ['item_id' => $this->dish()->id]);
        $this->post(route('checkout.store'), $this->checkout());
        $this->postJson(route('cart.add'), ['item_id' => $this->dish()->id]);
        $this->post(route('checkout.store'), $this->checkout());

        [$first, $second] = Order::orderBy('id')->get()->all();

        $this->assertSame('ZZ/2026-27/0001', $first->ensureInvoiceNumber());
        $this->assertSame('ZZ/2026-27/0002', $second->ensureInvoiceNumber());
        $this->assertSame('ZZ/2026-27/0001', $first->fresh()->ensureInvoiceNumber());

        $this->actingAs(User::where('email', 'manager@zamzam.test')->firstOrFail());
        $this->get(route('admin.orders.invoice', $first))->assertOk()->assertSee('TAX INVOICE');
    }

    public function test_the_app_can_quote_and_place_an_order_with_a_token(): void
    {
        $user = $this->customer();
        $dish = $this->dish();
        $basket = ['items' => [['id' => $dish->id, 'quantity' => 2]], 'type' => 'pickup'];

        $this->postJson('/api/v1/quote', $basket)->assertOk()->assertJsonPath('subtotal', fn (int|float $subtotal): bool => (float) $subtotal === (float) $dish->price * 2);
        $this->postJson('/api/v1/orders', $basket + ['payment_method' => 'cod'])->assertUnauthorized();

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/orders', $basket + ['payment_method' => 'cod'])
            ->assertCreated()
            ->assertJsonPath('order.total', number_format((float) $dish->price * 2, 2, '.', ''))
            ->assertJsonPath('payment', null);

        $order = Order::firstOrFail();
        $this->assertSame('app', $order->source);
        $this->assertSame($user->id, $order->user_id);

        $this->withToken($token)->getJson('/api/v1/orders/'.$order->order_number)->assertOk()->assertJsonPath('order.status', 'pending');
        $this->withToken($token)->postJson('/api/v1/addresses', ['label' => 'Home', 'address' => '12 Test Street'])->assertCreated();
    }
}
