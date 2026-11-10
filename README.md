# Zam Zam Family Restaurant

Website and management panel for Zam Zam Family Restaurant, Kairana.

Built with Laravel 13, Filament 5 (admin), Spatie roles & permissions, Tailwind CSS 4, Alpine.js and Razorpay.

## What is inside

**Public website** — home, menu with search and veg filter, gallery, guest videos and reviews, table booking, contact, online ordering (delivery / takeaway), coupons, order tracking, customer accounts (sign up with mobile number, order history, reorder, saved addresses).

**Admin panel** (`/admin`) — orders with one-click status, counter orders, printable bills, table bookings, dishes and categories, coupons, gallery, videos, Instagram feed, review approval, messages, customers, sales report with CSV export, staff, roles & permissions, website settings.

**API** (`/api/v1`) — read-only menu, gallery, videos and reviews, plus token login for a future mobile app.

## Setup

Requirements: PHP 8.3+, Composer, Node 20+, MySQL 8 (InnoDB).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env`: set `APP_URL`, the `DB_*` values, and choose your own `ADMIN_EMAIL` and `ADMIN_PASSWORD`. Then:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan filament:assets
npm run build
```

Sign in at `/admin` with the admin email and password you put in `.env`. Change the seeded staff passwords before going live.

## Configuration

| What | Where |
| --- | --- |
| Restaurant details, hero text, ordering rules | Admin → Website settings |
| Razorpay keys and webhook secret | Admin → Website settings → Razorpay (or `RAZORPAY_*` in `.env`) |
| Instagram access token | Admin → Website settings → Instagram & social |
| Password reset emails | `MAIL_*` in `.env` (defaults to the log file) |

Razorpay webhook URL: `https://your-domain/razorpay/webhook` (events `payment.captured`, `payment.failed`).

Hourly Instagram sync needs the Laravel scheduler: `* * * * * php artisan schedule:run`.

## Notes

- Seeded dishes, prices and photos are demo content. The photos are hot-linked stock images from Unsplash; replace them with real ones in the admin panel.
- Roles: `super_admin`, `manager`, `order_staff`, `content_editor`, `customer`. Permissions are `view|create|update|delete <module>`.
