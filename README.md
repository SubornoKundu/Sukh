# ShopEase

A full e-commerce site built with **Laravel 13, Vue 3, Inertia, Tailwind 4 and MySQL**, with a customer storefront and an admin panel.

## Features

**Customer panel**
- Register / login with email + password (no email verification), remember me, forgot / reset password, login throttling
- Home slider, brands, categories, product search, filters (category, brand, price range, in stock) and sorting
- Product pages with ratings and verified-purchase reviews
- Cart, wishlist, coupon codes, free-shipping threshold
- Checkout with **Cash on Delivery** or **online payment** (demo SSLCommerz: bKash / Nagad / Rocket / Card)
- Order history, live order timeline, cancel a pending order, printable invoice
- Profile with default delivery address and password change
- Email notifications for order updates (written to the log by default)

**Admin panel (`/admin`)**
- Dashboard: revenue, orders, customers, 14-day revenue chart, top sellers, low-stock list
- Orders: search and filter, update status (pending -> processing -> shipped -> delivered / cancel), payment status, invoice. Cancelling puts stock and coupon use back
- Products: add / edit / delete with image upload, sale price, stock, featured and visibility
- Categories, brands, home banners, coupons (percent or fixed, minimum order, usage limits, dates)
- Customers: list, order history, suspend / re-activate
- Reviews: hide or delete

## Test accounts

| Role  | Email                 | Password      |
|-------|-----------------------|---------------|
| Admin | admin@shopease.test   | `Admin@12345` |
| User  | user@shopease.test    | `User@12345`  |

Sample coupons: `WELCOME10` (10% off, min 1000), `SAVE200` (200 off, min 2000), `MEGA25` (25% off, min 20000).

> Change or delete these accounts before putting a real store online.

## Setup

```bash
composer install
npm install
cp .env.example .env        # then set DB_* values
php artisan key:generate
php artisan migrate:fresh --seed
npm run build               # or: npm run dev
php artisan serve
```

Already have a database? Run `php artisan migrate` (adds the new columns and tables) and then
`php artisan db:seed --class=UserSeeder` and `php artisan db:seed --class=CouponSeeder`.

Optional `.env` settings: `SHOP_SHIPPING_FEE=60`, `SHOP_FREE_SHIPPING_OVER=5000`, `SHOP_LOW_STOCK_THRESHOLD=5`.
Set `MAIL_MAILER=smtp` (and the `MAIL_*` values) to send real emails; with the default `log` mailer they appear in `storage/logs/laravel.log`.

## Going live (checklist)

- In `.env` set `APP_ENV=production` and **`APP_DEBUG=false`** (with `true`, visitors can see file paths and code when something fails).
- For a real shop also set `SHOP_DEMO_LOGIN=false` and `OTP_DEBUG_DISPLAY=false` (they exist only for demos).
- Run `php artisan config:cache && php artisan route:cache` after changing `.env`, and `npm run build` after changing front-end code.
- Help pages (About, Contact, Returns, Privacy, Terms) and "Track an order" are in the footer; contact details come from Admin > Settings.

## Deploying to a server

Ready-made files are in the `deploy/` folder and `.env.production.example`.

0. **The server needs PHP 8.4.1 or newer** (the locked Symfony 8 packages will not install on 8.3), MySQL 8 / MariaDB 10.6+, Composer 2 and Node 20+ (Node is only needed to build; you can also build on your computer and upload `public/build`).
1. Upload the project (or `git pull`). Point the website to the `public/` folder.
2. `cp .env.production.example .env`, fill the CHANGE lines, then `php artisan key:generate`.
3. Server settings (already prepared for you):
   - nginx: copy `deploy/nginx.conf.example` (has `client_max_body_size 64M`). Apache: `public/.htaccess` already sets the limits.
   - PHP-FPM: copy `deploy/php-shopease.ini` to the `conf.d` folder (`upload_max_filesize 20M`, `post_max_size 64M`, `max_file_uploads 30`, `memory_limit 256M`) and restart PHP-FPM. On shared hosting `public/.user.ini` does the same.
   - PHP extensions needed: pdo_mysql, mbstring, openssl, fileinfo, ctype, tokenizer, xml, curl, **gd** (with jpeg/png/webp) and **exif**.
4. `bash deploy/deploy.sh` (add `--demo` on a client demo site). It installs packages, builds the front end, runs `migrate --force`, caches config/routes/views, restarts the queue worker and runs the check below.
5. Queue worker (only if `QUEUE_CONNECTION` is `database`/`redis`): `deploy/supervisor-queue.conf.example`.
6. `php artisan shop:check` tells you in plain words what is still wrong (FAIL = fix, WARN = look). Run it after every deploy. Also open `phpinfo()` once on the website itself, because the command line can use a different php.ini.
7. Keep `public/images/uploads/` between deploys (do not delete it) and back it up together with the database.

## Product pictures

Each product has a main picture plus up to 10 more (Admin → Products → Add/Edit → *More pictures*; pick several files at once, remove with the X, or press *Make main* to swap one into the main spot). Shoppers see thumbnails and arrows on the product page and can tap a picture to open it full screen (arrow keys, swipe and Esc work). Run `php artisan migrate` once to create the `product_images` table.

## Uploaded pictures fit automatically

Every picture an admin uploads is fitted to the place it is shown (needs PHP's GD extension, which Herd and most hosts have; without it the original file is kept as before): products and variants become square with nothing cut off (matching-colour borders), banners are cut to 1600x640, categories to 4:3, brand logos are put on a square with a transparent background. Phone photos are turned upright, huge pictures are shrunk and hidden camera data (GPS) is removed. Pictures uploaded before this update are not changed.

## Scaling (many customers + many admins at once)

Run `php artisan migrate` once (adds performance indexes). For a busy live site set in `.env`:

```
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis        # or database
SESSION_DRIVER=redis     # or database
QUEUE_CONNECTION=redis   # or database
```

Then keep a queue worker running so order emails never slow down checkout:
`php artisan queue:work --tries=3` (use Supervisor/systemd to keep it alive).

Optional cache tuning (seconds, `0` = off): `SHOP_STOREFRONT_CACHE=120`, `SHOP_POPULAR_CACHE=600`, `SHOP_STATS_CACHE=120`.
The home page cache refreshes automatically whenever a product, category, brand, slider or variant changes.

Also recommended: PHP OPcache on, `php artisan config:cache route:cache view:cache`, a CDN for `/storage` and `/build`, and a MySQL server sized for the traffic.

## Tests

```bash
php artisan test
```

Covers auth, password reset, admin access control, checkout (COD and online), coupons, stock handling, cancellations, reviews and every admin CRUD screen.

## Notes

- Uploaded images are saved in `public/images/uploads/`, so no `storage:link` is needed.
- Payment is a demo gateway. To take real payments, replace `app/Services/SslCommerzDemoGateway.php` with the real SSLCommerz integration.
- Real-world extras not included: delivery-carrier integrations, multi-vendor, multiple currencies.

## New in this update

- **Storefront:** live search suggestions, cart slide-out drawer (real-time badge), WhatsApp chat button, multi-level category menu, trust badges, best sellers, recently viewed, "most popular" sort, footer with contact/social/quick links.
- **Product page:** sale price with strike-through, size/type/colour variants (own stock/price/photo), low-stock urgency tag, Overview/Specifications/Shipping/Reviews/Q&A tabs, frequently-bought-together, sticky mobile "Add to Cart / Order Now" bar.
- **Checkout:** one-page checkout, saved addresses, coupon, payment = COD / bKash / Nagad (manual, transaction ID) / online gateway demo, order success screen.
- **Customer:** demo + "Google" login (demo only), order tracking timeline (auto-refresh), address book.
- **Admin:** daily sales summary, interactive 7/14/30/90-day charts, status-filtered recent orders, inventory page with low-stock emails and restock suggestions, CSV bulk product upload (template included), category/sub-category manager, product variants/specs editor, packing slips + invoices, courier booking mock (Pathao/Steadfast), product Q&A moderation.

### After pulling these changes

```
php artisan migrate
php artisan db:seed          # adds sub-categories, variants, specs, demo orders, Q&A
npm run build
```

Optional `.env` values: `SHOP_WHATSAPP`, `SHOP_PHONE`, `SHOP_EMAIL`, `SHOP_ADDRESS`, `SHOP_BKASH_NUMBER`, `SHOP_NAGAD_NUMBER`, `SHOP_FACEBOOK`, `SHOP_INSTAGRAM`, `SHOP_YOUTUBE`, `SHOP_X`, `SHOP_DEMO_LOGIN` (set `false` in production). Google login and courier booking are demo mocks.

### Demo mode (for clients trying the site)

With `SHOP_DEMO_LOGIN=true` (default):
- Login and Register pages show **Customer** and **Admin** buttons that sign in the seeded test accounts (`user@shopease.test` / `admin@shopease.test`) in one click.
- The admin sidebar has a **Reset demo data** button. After a Yes/No confirmation it runs `migrate:fresh --seed` and deletes pictures uploaded through the admin panel (`public/images/uploads`), restoring the original sample data, then signs the admin back in.

**Set `SHOP_DEMO_LOGIN=false` before running a real store** - it removes both the one-click logins and the reset button (the routes return 404).

## Site settings (Admin → Settings)

Edit without touching code: site name (header, footer, browser title, emails, invoices), logo, favicon, brand colour,
announcement bar, tagline, footer line, search-engine description, contact details, social links, WhatsApp,
shipping fee, free-shipping limit, low-stock alert level and bKash/Nagad numbers.
Saved values live in the `settings` table (run `php artisan migrate`) and override the defaults in `.env` / `config/shop.php`.
"Reset demo data" also clears the settings and the uploaded logo/icon.
