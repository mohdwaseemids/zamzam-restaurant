<?php

namespace App\Filament\Pages;

use App\Filament\Support\Upload;
use App\Models\Setting;
use App\Services\InstagramService;
use App\Services\StoreHours;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class SiteSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Website settings';

    protected static ?string $title = 'Website settings';

    protected static ?string $slug = 'settings';

    public ?array $data = [];

    /** Settings that hold a secret: never sent back to the browser, only replaced when a new value is typed. */
    protected const SECRETS = ['razorpay_key_secret', 'razorpay_webhook_secret', 'instagram_access_token', 'fcm_service_account'];

    /** Settings shown as on/off switches. */
    protected const TOGGLES = [
        'ordering_enabled', 'delivery_enabled', 'cod_enabled', 'online_payment_enabled', 'enforce_hours', 'loyalty_enabled',
        'hours_mon_closed', 'hours_tue_closed', 'hours_wed_closed', 'hours_thu_closed', 'hours_fri_closed', 'hours_sat_closed', 'hours_sun_closed',
    ];

    public const DEFAULTS = [
        'site_name' => 'Zam Zam Family Restaurant',
        'tagline' => 'Authentic Flavours • Family Dining',
        'phone' => '',
        'whatsapp' => '',
        'email' => '',
        'address' => 'Kairana, Shamli, Uttar Pradesh',
        'opening_hours' => 'Every day · 11:00 AM – 11:30 PM',
        'map_embed_url' => '',
        'hero_eyebrow' => 'Kairana’s finest family dining',
        'hero_title' => 'Taste that brings families together',
        'hero_subtitle' => 'Charcoal-kissed mutton, slow-dum biryani and fresh river fish — served with the warmth of home.',
        'hero_image' => '',
        'hero_video' => '',
        'about_title' => 'A table where every guest is family',
        'about_text' => "Zam Zam Family Restaurant began with one promise — honest food, generous portions and a place where the whole family feels welcome.\n\nOur ustads marinate overnight, grind masalas by hand and cook on live charcoal, the way it has always been done.",
        'about_image' => '',
        'ordering_enabled' => '1',
        'delivery_enabled' => '1',
        'cod_enabled' => '1',
        'online_payment_enabled' => '1',
        'min_order' => '150',
        'delivery_charge' => '30',
        'free_delivery_above' => '500',
        'tax_percent' => '0',
        'razorpay_key_id' => '',
        'razorpay_key_secret' => '',
        'razorpay_webhook_secret' => '',
        'instagram_handle' => 'zamzamkairana',
        'instagram_url' => 'https://www.instagram.com/zamzamkairana/',
        'instagram_access_token' => '',
        'facebook_url' => '',
        'youtube_url' => '',
        'google_review_url' => '',
        'enforce_hours' => '1',
        'hours_mon_open' => '11:00', 'hours_mon_close' => '23:30', 'hours_mon_closed' => '0',
        'hours_tue_open' => '11:00', 'hours_tue_close' => '23:30', 'hours_tue_closed' => '0',
        'hours_wed_open' => '11:00', 'hours_wed_close' => '23:30', 'hours_wed_closed' => '0',
        'hours_thu_open' => '11:00', 'hours_thu_close' => '23:30', 'hours_thu_closed' => '0',
        'hours_fri_open' => '11:00', 'hours_fri_close' => '23:30', 'hours_fri_closed' => '0',
        'hours_sat_open' => '11:00', 'hours_sat_close' => '23:30', 'hours_sat_closed' => '0',
        'hours_sun_open' => '11:00', 'hours_sun_close' => '23:30', 'hours_sun_closed' => '0',
        'loyalty_enabled' => '1',
        'loyalty_earn_per_100' => '5',
        'loyalty_point_value' => '1',
        'loyalty_min_redeem' => '50',
        'loyalty_max_percent' => '50',
        'gstin' => '',
        'gst_legal_name' => '',
        'gst_sac' => '996331',
        'fssai_number' => '',
        'legal_privacy' => '',
        'legal_terms' => '',
        'legal_refund' => '',
        'fcm_service_account' => '',
    ];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage settings');
    }

    public function mount(): void
    {
        $data = array_merge(self::DEFAULTS, array_intersect_key(Setting::allCached(), self::DEFAULTS));

        foreach (self::TOGGLES as $toggle) {
            $data[$toggle] = (bool) $data[$toggle];
        }

        foreach (self::SECRETS as $secret) {
            $data[$secret] = '';
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $secretHint = fn (string $key) => filled(setting($key)) ? 'A value is saved. Type a new one only to replace it.' : 'Not set yet.';

        return $schema->statePath('data')->components([
            Tabs::make('Settings')->persistTabInQueryString()->tabs([
                Tab::make('Restaurant')->icon('heroicon-o-building-storefront')->schema([
                    TextInput::make('site_name')->required()->maxLength(100),
                    TextInput::make('tagline')->maxLength(150),
                    TextInput::make('phone')->tel()->label('Phone (calls)')->placeholder('+91 98xxxxxxxx'),
                    TextInput::make('whatsapp')->tel()->label('WhatsApp number')->placeholder('9198xxxxxxxx')->helperText('With country code, digits only.'),
                    TextInput::make('email')->email(),
                    TextInput::make('opening_hours'),
                    Textarea::make('address')->rows(2)->columnSpanFull(),
                    Textarea::make('map_embed_url')->label('Google Maps embed link')->rows(2)->columnSpanFull()
                        ->helperText('Google Maps → Share → Embed a map → copy only the link inside src="…".'),
                ])->columns(2),

                Tab::make('Home page')->icon('heroicon-o-home')->schema([
                    TextInput::make('hero_eyebrow')->label('Small line above heading'),
                    TextInput::make('hero_title')->label('Main heading')->required(),
                    Textarea::make('hero_subtitle')->rows(2)->columnSpanFull(),
                    Upload::image('hero_image', 'site', 'Hero background photo'),
                    Upload::video('hero_video', 'site', 'Hero background video (optional)'),
                    TextInput::make('about_title')->columnSpanFull(),
                    Textarea::make('about_text')->rows(5)->columnSpanFull(),
                    Upload::image('about_image', 'site', 'About section photo')->columnSpanFull(),
                ])->columns(2),

                Tab::make('Ordering')->icon('heroicon-o-shopping-cart')->schema([
                    Toggle::make('ordering_enabled')->label('Accept online orders'),
                    Toggle::make('delivery_enabled')->label('Home delivery available'),
                    Toggle::make('cod_enabled')->label('Allow cash on delivery / pay at counter'),
                    Toggle::make('online_payment_enabled')->label('Allow online payment (Razorpay)'),
                    TextInput::make('min_order')->numeric()->prefix('₹')->label('Minimum order'),
                    TextInput::make('delivery_charge')->numeric()->prefix('₹'),
                    TextInput::make('free_delivery_above')->numeric()->prefix('₹')->helperText('0 = never free.'),
                    TextInput::make('tax_percent')->numeric()->suffix('%')->label('GST / tax')->helperText('0 = prices include tax.'),
                ])->columns(2),

                Tab::make('Timings')->icon('heroicon-o-clock')->schema([
                    Toggle::make('enforce_hours')->label('Accept online orders only during these hours')->columnSpanFull()
                        ->helperText('Outside these hours customers can browse the menu but cannot place an order. A closing time earlier than the opening time means "open past midnight".'),
                    ...collect(StoreHours::DAYS)->flatMap(fn (string $label, string $key): array => [
                        TextInput::make("hours_{$key}_open")->label($label.' — opens')->type('time')->required(),
                        TextInput::make("hours_{$key}_close")->label('Closes')->type('time')->required(),
                        Toggle::make("hours_{$key}_closed")->label('Closed all day')->inline(false),
                    ])->all(),
                ])->columns(3),

                Tab::make('Loyalty')->icon('heroicon-o-gift')->schema([
                    Toggle::make('loyalty_enabled')->label('Loyalty points for signed-in customers')->columnSpanFull(),
                    TextInput::make('loyalty_earn_per_100')->numeric()->minValue(0)->label('Points earned per ₹100 spent'),
                    TextInput::make('loyalty_point_value')->numeric()->minValue(0.01)->prefix('₹')->label('Value of one point'),
                    TextInput::make('loyalty_min_redeem')->numeric()->minValue(0)->label('Minimum points before they can be used'),
                    TextInput::make('loyalty_max_percent')->numeric()->minValue(1)->maxValue(100)->suffix('%')->label('Most of an order that points can pay for'),
                ])->columns(2),

                Tab::make('GST & legal')->icon('heroicon-o-document-text')->schema([
                    TextInput::make('gstin')->label('GSTIN')->maxLength(15)->placeholder('09ABCDE1234F1Z5'),
                    TextInput::make('gst_legal_name')->label('Legal business name on invoices')->helperText('Leave empty to use the restaurant name.'),
                    TextInput::make('gst_sac')->label('SAC code')->helperText('996331 = restaurant services.'),
                    TextInput::make('fssai_number')->label('FSSAI licence number'),
                    Textarea::make('legal_privacy')->label('Privacy policy')->rows(6)->columnSpanFull()
                        ->helperText('Leave empty to show the built-in template. Markdown is supported (## Heading, - list).'),
                    Textarea::make('legal_terms')->label('Terms & conditions')->rows(6)->columnSpanFull(),
                    Textarea::make('legal_refund')->label('Refund & cancellation policy')->rows(6)->columnSpanFull(),
                ])->columns(2),

                Tab::make('App & push')->icon('heroicon-o-device-phone-mobile')->schema([
                    Textarea::make('fcm_service_account')->label('Firebase service account (JSON)')->rows(5)->columnSpanFull()
                        ->helperText(fn () => $secretHint('fcm_service_account').' Firebase console → Project settings → Service accounts → Generate new private key, then paste the whole file here. Used to send order updates to the mobile app.')
                        ->rule('nullable')->rule('json'),
                ]),

                Tab::make('Razorpay')->icon('heroicon-o-credit-card')->schema([
                    TextInput::make('razorpay_key_id')->label('Key ID')->placeholder('rzp_test_… or rzp_live_…')
                        ->helperText('Razorpay Dashboard → Account & Settings → API Keys.'),
                    TextInput::make('razorpay_key_secret')->label('Key secret')->password()->autocomplete('new-password')
                        ->helperText(fn () => $secretHint('razorpay_key_secret')),
                    TextInput::make('razorpay_webhook_secret')->label('Webhook secret')->password()->autocomplete('new-password')->columnSpanFull()
                        ->helperText(fn () => $secretHint('razorpay_webhook_secret').' Webhook URL: '.url('/razorpay/webhook').' (events: payment.captured, payment.failed).'),
                ])->columns(2),

                Tab::make('Instagram & social')->icon('heroicon-o-camera')->schema([
                    TextInput::make('instagram_handle')->prefix('@'),
                    TextInput::make('instagram_url')->url(),
                    TextInput::make('instagram_access_token')->label('Instagram access token')->password()->autocomplete('new-password')->columnSpanFull()
                        ->helperText(fn () => $secretHint('instagram_access_token').' With a token, posts sync automatically every hour. Without one, add posts by hand in Media → Instagram feed.'),
                    TextInput::make('facebook_url')->url(),
                    TextInput::make('youtube_url')->url(),
                    TextInput::make('google_review_url')->url()->label('Google review link')->columnSpanFull(),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncInstagram')
                ->label('Sync Instagram now')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (InstagramService $instagram) {
                    try {
                        $count = $instagram->sync();
                        Notification::make()->title("Instagram synced: {$count} posts")->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Instagram sync failed')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();

        foreach (self::SECRETS as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]); // keep the stored secret
            }
        }

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? '1' : '0';
            }
        }

        Setting::putMany(array_intersect_key($data, self::DEFAULTS));

        foreach (self::SECRETS as $secret) {
            $this->data[$secret] = '';
        }

        Notification::make()->title('Settings saved')->success()->send();
    }
}
