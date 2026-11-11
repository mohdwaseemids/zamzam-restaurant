# Setup guide

How to get Zam Zam Family Restaurant running on a new computer or server, step by step.

- [1. What you need](#1-what-you-need)
- [2. Install on your computer (WAMP / XAMPP / Laragon)](#2-install-on-your-computer-wamp--xampp--laragon)
- [3. Sign in and what the demo data contains](#3-sign-in-and-what-the-demo-data-contains)
- [4. First things to change in the admin panel](#4-first-things-to-change-in-the-admin-panel)
- [5. Connect outside services](#5-connect-outside-services)
- [6. Put it on a live server](#6-put-it-on-a-live-server)
- [7. Updating later](#7-updating-later)
- [8. Problems and fixes](#8-problems-and-fixes)

## 1. What you need

| Software | Version | Check with |
| --- | --- | --- |
| PHP | 8.3 or newer | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 or newer | `node -v` |
| MySQL | 8.x (or MariaDB 10.6+) | — |
| Git | any | `git --version` |

PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`, `curl`. WAMP, XAMPP and Laragon include all of them.

## 2. Install on your computer (WAMP / XAMPP / Laragon)

Run these commands one at a time inside your web folder (`C:\wamp64\www` for WAMP, `C:\xampp\htdocs` for XAMPP).

**Step 1 — Get the code**

```bash
git clone https://github.com/mohdwaseemids/zamzam-restaurant.git zamzam
```

```bash
cd zamzam
```

**Step 2 — Install packages**

```bash
composer install
```

```bash
npm install
```

**Step 3 — Create the settings file**

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

**Step 4 — Create the database**

Open phpMyAdmin (`http://localhost/phpmyadmin`) and create an empty database named `zamzam` with collation `utf8mb4_unicode_ci`.

**Step 5 — Edit `.env`**

Open the `.env` file in a text editor and set these lines:

```ini
APP_URL=http://localhost/zamzam/public

DB_DATABASE=zamzam
DB_USERNAME=root
DB_PASSWORD=

ADMIN_EMAIL=you@example.com
ADMIN_PASSWORD=choose-a-strong-password
```

`ADMIN_EMAIL` and `ADMIN_PASSWORD` become the owner login for the admin panel. Pick the password **before** the next step.

**Step 6 — Create the tables and demo data**

```bash
php artisan migrate --seed
```

**Step 7 — Finish**

```bash
php artisan storage:link
```

```bash
php artisan filament:assets
```

```bash
npm run build
```

**Step 8 — Open it**

- Website: `http://localhost/zamzam/public`
- Admin panel: `http://localhost/zamzam/public/admin`

Use the WAMP / XAMPP address above. `php artisan serve` works on most computers too, but on some Windows machines it is very slow and pages load without styling.

## 3. Sign in and what the demo data contains

**Logins created by the seeder.** All four use the password you set as `ADMIN_PASSWORD`.

| Role | Email | Can do |
| --- | --- | --- |
| Owner (super admin) | your `ADMIN_EMAIL` | Everything |
| Manager | `manager@zamzam.test` | Everything except staff roles |
| Counter staff | `counter@zamzam.test` | Orders, counter orders, bookings |
| Content editor | `editor@zamzam.test` | Menu, coupons, gallery, videos, reviews |

Delete or change the three `@zamzam.test` accounts before going live (Admin → Staff & users).

**Demo content**

| What | Demo data |
| --- | --- |
| Menu | 7 categories, 28 dishes with photos and prices |
| Gallery | 14 photos |
| Delivery areas | 3 distance bands with different charges |
| Tables | 4 tables and 2 family halls, each with a QR code |
| Coupon | `WELCOME10` — 10% off above ₹300, up to ₹100 |
| Settings | Opening hours 11:00 AM – 11:30 PM, loyalty rules, ordering rules |
| Roles | 5 roles with their permissions |

Not seeded on purpose: customer reviews, guest videos and Instagram posts. Those sections appear on the website once you add real ones.

**About the demo photos.** The pictures are free stock photos from Unsplash, stored in `database/seeders/demo-photos` and copied to `storage/app/public/demo` when you seed. They are placeholders — they are not photos of the restaurant or its food. Replace them before going live.

## 4. First things to change in the admin panel

1. **Website settings → Restaurant**: phone, WhatsApp number, email, address, Google Maps link.
2. **Website settings → Timings**: real opening and closing time for each day. Online orders are refused outside these hours.
3. **Website settings → Home page**: hero photo, heading and the "Our story" text.
4. **Menu → Dishes and Categories**: real dishes, prices and photos. Set "Stock for today" only on dishes that can run out.
5. **Restaurant → Delivery areas**: the areas you really deliver to, with the charge for each. If you delete all areas, the single charge from Settings → Ordering is used.
6. **Restaurant → Tables & QR codes**: your real tables. Print each QR code and put it on the table.
7. **Website settings → Ordering**: minimum order, GST %, cash on delivery on or off.
8. **Website settings → GST & legal**: GSTIN, FSSAI number, and read the three policy pages.
9. **Website settings → Loyalty**: points per ₹100 and the value of a point, or switch loyalty off.

## 5. Connect outside services

**Razorpay (online payment)**

1. In the Razorpay Dashboard go to Account & Settings → API Keys and generate keys. Start with **test** keys.
2. Admin → Website settings → Razorpay: paste the Key ID and Key secret.
3. In Razorpay add a webhook to `https://your-domain/razorpay/webhook` with events `payment.captured` and `payment.failed`. Paste the webhook secret in the same settings tab.
4. Place a test order with "Pay online" and check that it shows as Paid in Admin → Orders.

"Pay online" only appears at checkout after the keys are saved.

**Email (password reset)**

Until email is set up, reset emails are only written to `storage/logs/laravel.log`. Add your SMTP details to `.env`:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=orders@your-domain.com
```

**Instagram feed**

Without a token, add posts by hand in Admin → Media → Instagram feed. For automatic sync, paste a long-lived Instagram access token in Website settings → Instagram & social and press "Sync Instagram now".

**Mobile app push notifications**

Firebase console → Project settings → Service accounts → Generate new private key. Paste the whole JSON file in Website settings → App & push.

## 6. Put it on a live server

1. Upload the project, or `git clone` it on the server.
2. Point the domain's document root at the **`public`** folder. If your hosting cannot do that, ask their support; never expose the project root.
3. Create a MySQL database and a `.env` file as in section 2, with these differences:

   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

4. Run:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   ```bash
   php artisan key:generate
   ```

   ```bash
   php artisan migrate --seed --force
   ```

   ```bash
   php artisan storage:link
   ```

   ```bash
   php artisan filament:assets
   ```

   ```bash
   npm install && npm run build
   ```

   ```bash
   php artisan optimize
   ```

   If the server has no Node.js, run `npm run build` on your computer and upload the `public/build` folder.

5. Add one cron job so the hourly Instagram sync runs:

   ```
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

6. Make sure the `storage` and `bootstrap/cache` folders are writable by the web server.
7. Turn on HTTPS (SSL). Razorpay needs it.
8. Set up a daily backup of the database and the `storage/app/public` folder — that folder holds every uploaded photo and video.

**Before you announce the site**

- [ ] Owner password changed, `@zamzam.test` staff accounts removed
- [ ] Real photos, dishes and prices in place of the demo ones
- [ ] Phone, WhatsApp, address and map filled in
- [ ] Opening hours and delivery areas correct
- [ ] One Razorpay test payment completed, then live keys entered
- [ ] Password reset email arrives
- [ ] Privacy, terms and refund pages read and corrected
- [ ] `APP_DEBUG=false`

## 7. Updating later

After pulling new code:

```bash
git pull
```

```bash
composer install --no-dev --optimize-autoloader
```

```bash
php artisan migrate --force
```

```bash
php artisan db:seed --force
```

```bash
npm install && npm run build
```

```bash
php artisan optimize
```

Running `db:seed` again is safe: it adds new permissions and missing settings and does not touch your dishes, orders or photos. It does reset the built-in roles (manager, order staff, content editor) to their standard permissions.

## 8. Problems and fixes

| Problem | Fix |
| --- | --- |
| Page shows plain text with no design | Run `npm run build`. If a file named `public/hot` exists and you are not running `npm run dev`, delete it. |
| Admin panel has no design | Run `php artisan filament:assets`. |
| Photos do not show | Run `php artisan storage:link`. Check `APP_URL` matches the address in your browser. |
| `Specified key was too long` while migrating | The database is using MyISAM. This project already forces InnoDB in `config/database.php`; make sure you did not change it, then run `php artisan migrate:fresh --seed`. |
| Admin login says 403 | That account is not staff. Sign in with `ADMIN_EMAIL`, or give the user a role in Staff & users. |
| Cannot sign in as owner | You changed `ADMIN_PASSWORD` after seeding. Reset it: `php artisan tinker --execute 'App\Models\User::where("email", "you@example.com")->first()->update(["password" => "new-password"]);'` |
| "Pay online" is missing at checkout | Razorpay keys are not saved, or online payment is switched off in Settings → Ordering. |
| Orders are refused with "We are closed" | Check Settings → Timings, or switch off "Accept online orders only during these hours". |
| Customers must pick an area but yours is missing | Add it in Restaurant → Delivery areas. |
| Large video will not upload | Raise `upload_max_filesize` and `post_max_size` in `php.ini` (the project asks for 256M through `public/.user.ini`, which only works when PHP runs as FastCGI). |
| Changes to `.env` are ignored | Run `php artisan optimize:clear`. |
| Site is very slow on `php artisan serve` | Use the WAMP / XAMPP address instead. |

**Run the automated tests**

```bash
php artisan test --compact
```

The tests use a temporary in-memory database and do not touch your real data.
