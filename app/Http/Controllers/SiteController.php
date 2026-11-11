<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\GalleryImage;
use App\Models\InstagramPost;
use App\Models\MenuItem;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Review;
use App\Models\Video;
use App\Services\AdminNotifier;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'categories' => Category::active()->withCount(['menuItems' => fn ($q) => $q->where('is_available', true)])->get(),
            'signature' => MenuItem::available()->where('is_featured', true)->with('category')->orderBy('sort_order')->take(8)->get(),
            'videos' => Video::active()->where('is_featured', true)->take(8)->get(),
            'gallery' => GalleryImage::active()->where('is_featured', true)->take(8)->get(),
            'reviews' => Review::approved()->where('is_featured', true)->latest()->take(9)->get(),
            'instagram' => InstagramPost::active()->take(8)->get(),
            'rating' => $this->ratingSummary(),
        ]);
    }

    public function menu()
    {
        $categories = Category::active()
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)])
            ->get()
            ->filter(fn (Category $c) => $c->menuItems->isNotEmpty())
            ->values();

        return view('site.menu', compact('categories'));
    }

    public function gallery()
    {
        return view('site.gallery', [
            'images' => GalleryImage::active()->get(),
            'categories' => GalleryImage::CATEGORIES,
            'instagram' => InstagramPost::active()->take(12)->get(),
        ]);
    }

    public function stories()
    {
        return view('site.stories', [
            'videos' => Video::active()->get(),
            'videoCategories' => Video::CATEGORIES,
            'reviews' => Review::approved()->with('menuItem:id,name')->latest()->paginate(12),
            'rating' => $this->ratingSummary(),
            'dishes' => MenuItem::available()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeReview(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'menu_item_id' => ['nullable', 'exists:menu_items,id'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('reviews', 'public');
        }

        $review = Review::create($data + ['is_approved' => false, 'source' => 'website']);
        AdminNotifier::newReview($review);

        return back()->with('success', 'Shukriya! Your review has been received and will appear after approval.')->withFragment('share');
    }

    public function reserve()
    {
        return view('site.reserve');
    }

    public function storeReservation(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:+3 months'],
            'time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'between:1,300'],
            'occasion' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], ['phone.regex' => 'Please enter a valid phone number.']);

        $reservation = Reservation::create($data + ['user_id' => auth()->id()]);
        AdminNotifier::newReservation($reservation);

        return back()->with('success', 'Your table request is received. We will call you shortly to confirm.');
    }

    public function contact()
    {
        return view('site.contact');
    }

    public function storeContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ], ['phone.regex' => 'Please enter a valid phone number.']);

        $message = ContactMessage::create($data);
        AdminNotifier::newMessage($message);

        return back()->with('success', 'Thank you! Your message has reached us.');
    }

    public function dish(MenuItem $menuItem): View
    {
        abort_unless($menuItem->category?->is_active, 404);

        $reviews = $menuItem->reviews()->approved()->latest()->take(12)->get();

        return view('site.dish', [
            'item' => $menuItem->load('category'),
            'photos' => collect([$menuItem->image])->merge($menuItem->photos ?? [])->filter()->map(fn (string $path): string => media_url($path))->values(),
            'reviews' => $reviews,
            'rating' => ['average' => round((float) $reviews->avg('rating'), 1), 'count' => $reviews->count()],
            'related' => MenuItem::available()->where('category_id', $menuItem->category_id)->whereKeyNot($menuItem->id)->orderBy('sort_order')->take(4)->get(),
        ]);
    }

    public function toggleFavourite(Request $request, MenuItem $menuItem): JsonResponse
    {
        $result = $request->user()->favourites()->toggle($menuItem->id);

        return response()->json([
            'saved' => $result['attached'] !== [],
            'ids' => $request->user()->favourites()->pluck('menu_items.id')->all(),
        ]);
    }

    /** Privacy policy, terms and refund policy. Text comes from Website settings → Legal, with a default template. */
    public function legal(string $page): View
    {
        $pages = [
            'privacy-policy' => ['Privacy Policy', 'legal_privacy'],
            'terms' => ['Terms & Conditions', 'legal_terms'],
            'refund-policy' => ['Refund & Cancellation Policy', 'legal_refund'],
        ];

        abort_unless(isset($pages[$page]), 404);

        [$title, $settingKey] = $pages[$page];
        $custom = setting($settingKey);

        return view('site.legal', [
            'title' => $title,
            'page' => $page,
            'customHtml' => filled($custom) ? str($custom)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) : null,
        ]);
    }

    /** A guest scanned the QR code on a table: remember the table and show the menu. */
    public function tableScan(string $token, CartService $cart): RedirectResponse
    {
        $table = RestaurantTable::where('token', $token)->where('is_active', true)->first();

        if (! $table) {
            return redirect()->route('menu')->with('error', 'This table code is not active. Please ask our staff for help.');
        }

        session([CartService::TABLE_KEY => $table->id]);

        return redirect()->route('menu')->with('success', 'You are ordering for '.$table->name.'. Add your dishes and we will bring them to your table.');
    }

    public function leaveTable(): RedirectResponse
    {
        session()->forget(CartService::TABLE_KEY);

        return redirect()->route('menu')->with('success', 'Table ordering switched off.');
    }

    /** Branded SVG shown wherever a photo has not been uploaded yet. */
    public function placeholder(Request $request)
    {
        $label = e(str($request->query('label', 'Zam Zam'))->limit(28, '')->toString());

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" preserveAspectRatio="xMidYMid slice">
  <defs>
    <radialGradient id="g" cx="50%" cy="38%" r="75%"><stop offset="0" stop-color="#3a2a14"/><stop offset="1" stop-color="#0d0a06"/></radialGradient>
    <pattern id="p" width="80" height="80" patternUnits="userSpaceOnUse"><path d="M40 0l40 40-40 40L0 40z M40 16l24 24-24 24-24-24z" fill="none" stroke="#c9a45c" stroke-opacity=".13"/></pattern>
  </defs>
  <rect width="800" height="800" fill="url(#g)"/><rect width="800" height="800" fill="url(#p)"/>
  <text x="400" y="372" text-anchor="middle" font-family="Georgia,serif" font-size="92" fill="#c9a45c" fill-opacity=".9" letter-spacing="10">ZZ</text>
  <path d="M300 410h200" stroke="#c9a45c" stroke-opacity=".5"/>
  <text x="400" y="462" text-anchor="middle" font-family="Georgia,serif" font-size="30" fill="#e9dcc3" fill-opacity=".85" letter-spacing="3">{$label}</text>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=604800']);
    }

    protected function ratingSummary(): array
    {
        $query = Review::approved();

        return ['average' => round((float) $query->avg('rating'), 1), 'count' => $query->count()];
    }
}
