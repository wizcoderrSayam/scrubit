# Scrubit: Cleaning Service Booking Platform

A Laravel 13 rebuild of [scrubit.ca](https://scrubit.ca). Customers pick a cleaning service, describe their needs in a guided chat, review the booking, pay online (or approve a quote first) and get a confirmation. Administrators manage services, bookings, quotes, payments, customers, content and settings.

**Booking flow:** Select Service → Customize (chat) → Review → Payment → Confirmation

## Stack

Laravel 13 · PHP 8.4 · MySQL · Blade + Tailwind CSS 4 + Alpine.js · Vite · Laravel Fortify (auth) · database queue · PHPUnit

## Local setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* and ADMIN_EMAIL / ADMIN_PASSWORD in .env
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install && npm run build      # or: npm run dev
php artisan serve
```

- Site: `http://127.0.0.1:8000`. Admin: `/admin`, logging in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- Emails go to `storage/logs/laravel.log` with `MAIL_MAILER=log`. Run `php artisan queue:work` to process them.
- Payments use the **demo gateway** by default: the checkout page offers buttons to simulate a successful, failed, cancelled or pending payment.

## Tests

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

The suite covers:
- pricing, taxes and add-ons
- drafts and the chat flow
- booking, quote and payment state rules
- webhook signatures and duplicate deliveries
- guest booking access
- admin authorization
- content escaping
- SEO routes

## Key concepts

| Topic | Where |
|---|---|
| Architecture, schema, state machines | [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) |
| Content inventory from the old site | [docs/SITE-INVENTORY.md](docs/SITE-INVENTORY.md) |
| Full production guide | [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) |
| Booking state machine | `app/Enums/BookingStatus.php`, `app/Services/BookingStateMachine.php` |
| Pricing (server-side only) | `app/Services/Pricing/BookingPricingService.php` |
| Guided chat (replaceable by AI later) | `app/Services/Chat/` (`ChatResponder` interface) |
| Payments and webhooks | `app/Services/Payments/` |
| Business settings (incl. "Starting $25/hr add on services" note) | Admin → Settings → Business |

## Scheduled tasks

`quotes:expire` (hourly), `payments:expire-checkouts` (every 15 min; also releases payments stuck in "processing" for 24h and flags them for review) and `bookings:prune-drafts` (daily) run via `php artisan schedule:run`.

## Deployment

### Requirements
PHP 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `gd` (WebP), `intl`, `bcmath` and `fileinfo` · MySQL 8+ / MariaDB 10.6+ · Composer 2 · Node 20+ (build only) · Nginx or Apache with its document root at `public/` · cron · a process supervisor · an SMTP or transactional email service.

### First install
```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate   # then edit .env (below)
php artisan migrate --force
php artisan db:seed --force      # services, pages, FAQs, testimonials, email templates, GST, availability, first admin
php artisan storage:link
npm ci && npm run build
php artisan optimize
```
Every seeder is safe to re-run and never overwrites content edited in the admin. The admin seeder only creates the account when no user with `ADMIN_EMAIL` exists.

### Production `.env`
| Key | Value |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://your-domain` (used in emails, the sitemap and canonical URLs) |
| `SESSION_SECURE_COOKIE` / `SESSION_ENCRYPT` | `true` / `true` |
| `MAIL_*` | Your email provider |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | First admin, used once by `db:seed`. Remove the password afterwards |
| `PAYMENT_DRIVER` | `demo` is the only built-in driver |
| `DEMO_WEBHOOK_SECRET` | Random string (falls back to `APP_KEY`) |
| `DEMO_GATEWAY_ALLOW_PRODUCTION` | Keep `false`; the demo gateway refuses to run in production otherwise |

Never commit `.env`.

### Background processes
- Queue worker (emails): `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`, kept running under Supervisor or systemd.
- Scheduler (cron): `* * * * * cd /path/to/scrubit && php artisan schedule:run >> /dev/null 2>&1`

### Payments
- Webhook endpoint: `POST {APP_URL}/webhooks/payments/{driver}`. It is CSRF-exempt, verifies the signature and stores each event once, so duplicate deliveries are ignored. Events for unknown payments get HTTP 409 so the provider retries.
- Bookings are confirmed only by a verified webhook, never by the customer returning to the site.
- Before launch, add a live driver (e.g. Stripe Checkout) implementing `App\Services\Payments\PaymentGateway`, register it in `config/payments.php`, and set `PAYMENT_DRIVER`. Refunds then call the provider; make that call idempotent with the provider's idempotency keys.

### Releasing an update
```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader
php artisan migrate --force && npm ci && npm run build
php artisan optimize && php artisan queue:restart
php artisan up
```

### Pre-launch checklist
- [ ] HTTPS enforced, `APP_DEBUG=false`, secure session cookies
- [ ] Admin password changed after first login
- [ ] A live payment driver tested end to end in the provider's test mode
- [ ] Queue worker and scheduler running (`php artisan schedule:list`)
- [ ] Real prices, add-ons, tax rates and availability set in the admin
- [ ] Draft Terms, Privacy and Cancellation pages reviewed by a legal advisor and the DRAFT notice removed
- [ ] Business claims and testimonials confirmed as accurate
- [ ] Old URLs redirect with 301 (`/recurring-cleaning`, `/product/<slug>`, `/enquiry`)
- [ ] `/sitemap.xml` submitted to Google Search Console
- [ ] Daily backups of the database and `storage/app/public` (service images)
