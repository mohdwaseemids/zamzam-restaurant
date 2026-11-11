<?php

namespace Database\Seeders;

use App\Filament\Pages\SiteSettings;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\GalleryImage;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo stock photos (from Unsplash) bundled in database/seeders/demo-photos.
     * They are copied into public storage so the site has pictures straight after setup.
     * Replace every one with real restaurant photos from the admin panel.
     */
    protected function photo(string $id, int $width = 1000): string
    {
        $source = database_path("seeders/demo-photos/{$id}.jpg");

        if (! is_file($source)) {
            return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$width}&q=75";
        }

        $path = "demo/{$id}.jpg";

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, file_get_contents($source));
        }

        return $path;
    }

    public function run(): void
    {
        $this->seedRolesAndUsers();
        $this->seedSettings();
        $this->seedMenu();
        $this->seedGallery();
        $this->seedOperations();
        $this->useBundledPhotos();
    }

    /** Demo delivery areas, dine-in tables and a welcome coupon, so every screen has something to show. */
    protected function seedOperations(): void
    {
        if (! DeliveryArea::exists()) {
            foreach ([['Within 2 km of the restaurant', 20, 0], ['2 to 5 km', 40, 200], ['5 to 8 km', 60, 400]] as $index => [$name, $charge, $minimum]) {
                DeliveryArea::create(['name' => $name, 'charge' => $charge, 'min_order' => $minimum, 'sort_order' => $index + 1]);
            }
        }

        if (! RestaurantTable::exists()) {
            foreach (['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Family Hall 1', 'Family Hall 2'] as $name) {
                RestaurantTable::create(['name' => $name, 'seats' => str_contains($name, 'Family') ? 8 : 4]);
            }
        }

        if (! Coupon::exists()) {
            Coupon::create([
                'code' => 'WELCOME10',
                'description' => '10% off your first online order',
                'type' => 'percent',
                'value' => 10,
                'min_order' => 300,
                'max_discount' => 100,
            ]);
        }
    }

    /** Older installs stored web links to the demo photos; point them at the bundled copies instead. */
    protected function useBundledPhotos(): void
    {
        $local = function (?string $value): ?string {
            if ($value && preg_match('~images\.unsplash\.com/photo-([0-9a-f-]+)\?~', $value, $match)) {
                $path = $this->photo($match[1]);

                return str_starts_with($path, 'http') ? null : $path;
            }

            return null;
        };

        foreach ([Category::class, MenuItem::class, GalleryImage::class] as $model) {
            $model::query()->where('image', 'like', 'https://images.unsplash.com/%')->get()->each(function ($record) use ($local): void {
                if ($path = $local($record->image)) {
                    $record->updateQuietly(['image' => $path]);
                }
            });
        }

        $settings = Setting::whereIn('key', ['hero_image', 'about_image'])->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $local($setting->value)])
            ->filter()->all();

        if ($settings !== []) {
            Setting::putMany($settings);
        }
    }

    protected function seedRolesAndUsers(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = ['categories', 'menu items', 'coupons', 'orders', 'reservations', 'gallery', 'videos', 'instagram', 'reviews', 'messages', 'delivery areas', 'tables', 'expenses', 'users', 'roles'];
        $abilities = ['view', 'create', 'update', 'delete'];

        foreach ($modules as $module) {
            foreach ($abilities as $ability) {
                Permission::findOrCreate("{$ability} {$module}");
            }
        }

        Permission::findOrCreate('access admin panel');
        Permission::findOrCreate('manage settings');
        Permission::findOrCreate('view reports');
        Permission::findOrCreate('view customers');

        $all = fn (array $modules) => collect($modules)->flatMap(fn ($m) => collect($abilities)->map(fn ($a) => "{$a} {$m}"))->all();

        // super_admin gets everything through Gate::before (AppServiceProvider).
        Role::findOrCreate('super_admin');

        Role::findOrCreate('manager')->syncPermissions([
            'access admin panel', 'manage settings', 'view reports', 'view customers',
            ...$all(['categories', 'menu items', 'coupons', 'orders', 'reservations', 'gallery', 'videos', 'instagram', 'reviews', 'messages', 'delivery areas', 'tables', 'expenses']),
            'view users',
        ]);

        Role::findOrCreate('order_staff')->syncPermissions([
            'access admin panel',
            'view orders', 'create orders', 'update orders', 'view customers', 'view tables',
            'view reservations', 'create reservations', 'update reservations',
            'view messages',
            'view menu items', 'update menu items',
        ]);

        Role::findOrCreate('content_editor')->syncPermissions([
            'access admin panel',
            ...$all(['categories', 'menu items', 'coupons', 'gallery', 'videos', 'instagram', 'reviews']),
        ]);

        Role::findOrCreate('customer');

        $password = env('ADMIN_PASSWORD', 'change-me-now');

        $users = [
            [env('ADMIN_EMAIL', 'admin@zamzam.test'), 'Zam Zam Owner', 'super_admin'],
            ['manager@zamzam.test', 'Restaurant Manager', 'manager'],
            ['counter@zamzam.test', 'Counter Staff', 'order_staff'],
            ['editor@zamzam.test', 'Content Editor', 'content_editor'],
        ];

        foreach ($users as [$email, $name, $role]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'is_active' => true]);
            $user->syncRoles([$role]);
        }
    }

    protected function seedSettings(): void
    {
        $existing = Setting::pluck('key')->all();

        $defaults = array_merge(SiteSettings::DEFAULTS, [
            'hero_image' => $this->photo('1599487488170-d11ec9c172f0', 1920),
            'about_image' => $this->photo('1577219491135-ce391730fb2c', 1100),
        ]);

        Setting::putMany(array_diff_key($defaults, array_flip($existing)));
    }

    protected function seedMenu(): void
    {
        if (Category::exists()) {
            return;
        }

        // [name, description, price, offer price, portion, photo, veg, spice, signature, bestseller]
        $menu = [
            ['Mutton Specials', 'Slow cooked on dum', 'Tender goat meat, hand-ground masalas and hours of patience.', '1596797038530-2c107229654b', [
                ['Mutton Korma', 'Royal Mughlai korma simmered in browned onion, curd and whole spices.', 380, null, 'Half · 4 pcs', '1596797038530-2c107229654b', false, 2, true, true],
                ['Mutton Nihari', 'Overnight slow-cooked shank stew finished with ginger, lemon and ghee.', 420, null, 'Full bowl', '1631452180519-c014fe946bc7', false, 2, true, false],
                ['Mutton Rogan Josh', 'Kashmiri style red gravy, rich with ratan jot and fennel.', 400, 360, 'Half · 4 pcs', '1585937421612-70a008356fbe', false, 3, false, false],
                ['Mutton Stew', 'Old-Delhi style ishtoo with whole red chillies and plenty of onion.', 390, null, 'Half · 4 pcs', '1645177628172-a94c1f96e6db', false, 2, false, false],
                ['Mutton Kadhai', 'Wok-tossed with capsicum, crushed coriander and fresh tomato.', 410, null, 'Half · 4 pcs', '1505253758473-96b7015fcd40', false, 3, false, true],
            ]],
            ['Chicken Specials', 'Family favourites', 'From buttery gravies to fiery kadhai — the dishes everyone asks for.', '1603894584373-5ac82b2ae398', [
                ['Butter Chicken', 'Tandoor-roasted chicken folded into silky tomato, butter and cream gravy.', 320, null, 'Half · 4 pcs', '1603894584373-5ac82b2ae398', false, 1, true, true],
                ['Chicken Changezi', 'A Delhi legend — creamy, tangy and mildly spiced roasted chicken curry.', 330, 299, 'Half · 4 pcs', '1565557623262-b51c2513a641', false, 2, true, false],
                ['Chicken Korma', 'Classic shaadi-wala korma with fried onion and kewra aroma.', 300, null, 'Half · 4 pcs', '1633945274405-b6c8069047b0', false, 2, false, false],
                ['Kadhai Chicken', 'Bold kadhai masala, capsicum and freshly pounded spices.', 310, null, 'Half · 4 pcs', '1628294895950-9805252327bc', false, 3, false, false],
                ['Chicken Fry', 'Crisp, juicy deep-fried chicken with our secret red masala.', 280, null, '4 pcs', '1610057099431-d73a1c9d2f2f', false, 2, false, true],
            ]],
            ['Fish & Seafood', 'Fresh catch daily', 'River fish, cleaned in house and cooked to order.', '1519708227418-c8fd9a32b7a2', [
                ['Fish Fry', 'Crisp besan-coated fish with ajwain, lemon and green chutney.', 340, null, '250 g', '1519708227418-c8fd9a32b7a2', false, 2, true, true],
                ['Fish Curry', 'Home-style mustard and tomato gravy with boneless fish.', 360, null, 'Half', '1574484284002-952d92456975', false, 2, false, false],
                ['Tandoori Fish Tikka', 'Boneless chunks marinated in hung curd and carom, roasted in the tandoor.', 380, 349, '6 pcs', '1467003909585-2f8a72700288', false, 2, false, false],
            ]],
            ['Biryani & Rice', 'Dum pukht', 'Long-grain basmati layered with meat and sealed on slow fire.', '1563379091339-03b21ab4a4f8', [
                ['Mutton Dum Biryani', 'Aged basmati, tender mutton, saffron and fried onion — sealed and slow cooked.', 360, null, 'Full plate', '1563379091339-03b21ab4a4f8', false, 2, true, true],
                ['Chicken Dum Biryani', 'Fragrant chicken biryani served with raita and salan.', 260, null, 'Full plate', '1589302168068-964664d93dc0', false, 2, true, true],
                ['Family Biryani Handi', 'One kilo of chicken biryani in a clay handi — perfect for four.', 899, 799, '1 kg · serves 4', '1512058564366-18510be2db19', false, 2, false, false],
                ['Jeera Rice', 'Steamed basmati tempered with cumin and ghee.', 140, null, 'Full plate', '1512058564366-18510be2db19', true, 0, false, false],
            ]],
            ['Tandoor & Kebabs', 'Live charcoal', 'Marinated overnight, grilled over glowing coals.', '1599487488170-d11ec9c172f0', [
                ['Mutton Seekh Kebab', 'Hand-minced mutton with green chilli and coriander, grilled on skewers.', 320, null, '4 pcs', '1599487488170-d11ec9c172f0', false, 2, true, true],
                ['Chicken Tikka', 'Boneless chicken in red tandoori marinade, charred at the edges.', 280, null, '6 pcs', '1555939594-58d7cb561ad1', false, 2, false, true],
                ['Tandoori Chicken', 'The classic — bone-in chicken, smoky and juicy.', 420, 380, 'Full bird', '1610057099431-d73a1c9d2f2f', false, 2, false, false],
                ['Afghani Malai Tikka', 'Creamy cashew and cheese marinade, mild and melt-in-mouth.', 300, null, '6 pcs', '1567188040759-fb8a883dc6d8', false, 1, false, false],
                ['Paneer Tikka', 'Cottage cheese, capsicum and onion from the tandoor.', 240, null, '6 pcs', '1567188040759-fb8a883dc6d8', true, 2, false, false],
            ]],
            ['Breads & Sides', 'Straight from the tandoor', 'Hot rotis and the little things that complete the meal.', '1565557623262-b51c2513a641', [
                ['Butter Naan', 'Soft tandoori naan brushed with butter.', 40, null, '1 pc', '1565557623262-b51c2513a641', true, 0, false, false],
                ['Rumali Roti', 'Paper-thin handkerchief roti, tossed by hand.', 20, null, '1 pc', '1505253758473-96b7015fcd40', true, 0, false, false],
                ['Khameeri Roti', 'Fluffy fermented tandoori roti — made for nihari and korma.', 25, null, '1 pc', '1565557623262-b51c2513a641', true, 0, false, false],
            ]],
            ['Drinks & Desserts', 'A sweet ending', 'Cool down and finish on something sweet.', '1488477181946-6428a0291777', [
                ['Shahi Tukda', 'Fried bread soaked in saffron rabri, topped with nuts.', 120, null, '2 pcs', '1488477181946-6428a0291777', true, 0, false, true],
                ['Fresh Lime Soda', 'Sweet, salted or mixed.', 60, null, 'Glass', '1556679343-c7306c1976bc', true, 0, false, false],
                ['Rooh Afza Sharbat', 'The classic rose cooler, served chilled.', 50, null, 'Glass', '1497534446932-c925b458314e', true, 0, false, false],
            ]],
        ];

        foreach ($menu as $c => [$name, $tagline, $description, $photo, $items]) {
            $category = Category::create([
                'name' => $name,
                'tagline' => $tagline,
                'description' => $description,
                'image' => $this->photo($photo, 900),
                'sort_order' => $c + 1,
            ]);

            foreach ($items as $i => [$itemName, $itemDescription, $price, $sale, $portion, $itemPhoto, $veg, $spice, $featured, $bestseller]) {
                MenuItem::create([
                    'category_id' => $category->id,
                    'name' => $itemName,
                    'description' => $itemDescription,
                    'price' => $price,
                    'sale_price' => $sale,
                    'portion' => $portion,
                    'image' => $this->photo($itemPhoto, 900),
                    'is_veg' => $veg,
                    'spice_level' => $spice,
                    'is_featured' => $featured,
                    'is_bestseller' => $bestseller,
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }

    protected function seedGallery(): void
    {
        if (GalleryImage::exists()) {
            return;
        }

        $images = [
            ['Charcoal seekh on the grill', '1599487488170-d11ec9c172f0', 'food', true],
            ['Evening at the restaurant', '1517248135467-4c7edcad34c4', 'ambience', true],
            ['Dum biryani, just opened', '1563379091339-03b21ab4a4f8', 'food', true],
            ['A table full of favourites', '1504674900247-0877df9cc836', 'food', true],
            ['Family hall', '1552566626-52f8b828add9', 'ambience', true],
            ['Our ustad at work', '1577219491135-ce391730fb2c', 'kitchen', true],
            ['Celebrations with family', '1528605248644-14dd04022da1', 'events', true],
            ['Kebab platter', '1555939594-58d7cb561ad1', 'food', true],
            ['Dinner seating', '1550966871-3ed3cdb5ed0c', 'ambience', false],
            ['Fresh from the kitchen', '1556910103-1c02745aae4d', 'kitchen', false],
            ['Sharing is the tradition', '1466978913421-dad2ebd01d17', 'events', false],
            ['Party spread', '1600891964599-f61ba0e24092', 'events', false],
            ['Butter chicken', '1603894584373-5ac82b2ae398', 'food', false],
            ['Terrace seating', '1559339352-11d035aa65de', 'ambience', false],
        ];

        foreach ($images as $i => [$title, $photo, $category, $featured]) {
            GalleryImage::create([
                'title' => $title,
                'image' => $this->photo($photo, 1200),
                'category' => $category,
                'is_featured' => $featured,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
