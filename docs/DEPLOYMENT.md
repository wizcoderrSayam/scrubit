# Deployment Guide

For whoever installs and runs Scrubit on a server: requirements, first install, configuration, background workers, payments, releases and the launch checklist.

## 1. Requirements

- PHP 8.3+ (tested on 8.4) with `pdo_mysql`, `mbstring`, `openssl`, `gd` (WebP support), `intl`, `bcmath`, `fileinfo`
- MySQL 8+ or MariaDB 10.6+
- Composer 2; Node.js 20+ and npm (build step only)
- Nginx or Apache with its document root at `public/`
- Cron (scheduler) and Supervisor or systemd (queue worker)
- An SMTP or transactional email service

## 2. First install

```bash
git clone <repository> scrubit && cd scrubit
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# edit .env (section 3)
php artisan migrate --force
php artisan db:seed --force      # services + images, pages, FAQs, testimonials, email templates, GST, availability, first admin
php artisan storage:link
npm ci && npm run build
php artisan optimize             # caches config, routes, views, events
```

Every seeder is safe to re-run and never overwrites content edited in the admin. The admin seeder only creates the account if no user with `ADMIN_EMAIL` exists.

## 3. Environment (`.env`)

| Key | Production value / notes |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://your-domain` (emails, sitemap, canonical URLs) |
| `DB_*` | MySQL credentials |
| `SESSION_SECURE_COOKIE` / `SESSION_ENCRYPT` | `true` / `true` |
| `QUEUE_CONNECTION` | `database` (default) or `redis`. All emails are queued |
| `CACHE_STORE` | `database` (default) or `redis` |
| `MAIL_*` | Your email provider |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First administrator, used once by `db:seed`. Remove the password afterwards |
| `BUSINESS_*` | Initial defaults only. Live values are edited in **Admin → Settings → Business** |
| `PAYMENT_DRIVER` | `demo` (the only built-in driver, see §6) |
| `PAYMENT_CHECKOUT_TTL_MINUTES` | Checkout session lifetime (default 30) |
| `DEMO_WEBHOOK_SECRET` | Random string used to sign demo webhooks (falls back to `APP_KEY`) |
| `DEMO_GATEWAY_ALLOW_PRODUCTION` | Keep `false`. The demo gateway refuses to run in production otherwise |

Never commit `.env`. Use `php artisan env:encrypt` or your host's secret store.

## 4. Background processes

Queue worker (booking, quote, payment and enquiry emails):

```ini
# /etc/supervisor/conf.d/scrubit-worker.conf
[program:scrubit-worker]
command=php /var/www/scrubit/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/scrubit/storage/logs/worker.log
```

Scheduler, which runs:
- `quotes:expire` hourly
- `payments:expire-checkouts` every 15 minutes (also releases payments stuck in "processing" for 24h)
- `bookings:prune-drafts` daily

```cron
* * * * * cd /var/www/scrubit && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Web server (Nginx example)

```nginx
server {
    server_name your-domain;
    root /var/www/scrubit/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
    location ~ /\.(?!well-known) { deny all; }
    client_max_body_size 20M;   # gallery uploads: up to 10 images x 4 MB
}
```

Serve over HTTPS only. `storage/` and `bootstrap/cache/` must be writable by the web user. `robots.txt` and `sitemap.xml` are generated routes, so do not add static files with those names to `public/`.

## 6. Payments

- **Demo driver:** a simulated hosted checkout (success, failure, cancel, pending) with HMAC-signed webhooks. Use it for staging and acceptance tests only.
- **Webhook endpoint:** `POST {APP_URL}/webhooks/payments/{driver}`. It is CSRF-exempt and signature-verified, and stores each event once in `payment_events`, so duplicates are ignored. Events for unknown payments get HTTP 409 so the provider retries.
- Bookings are confirmed **only** by a verified webhook event, never by the customer returning to the site.
- **Before launch:** implement a live driver (e.g. Stripe Checkout) as a class implementing `App\Services\Payments\PaymentGateway`, register it under `drivers` in `config/payments.php`, set `PAYMENT_DRIVER`, and point the provider's webhook at the URL above. Use the provider's idempotency keys for refunds. **Admin → Settings → Payments** shows the active driver and webhook URL.

## 7. Releasing an update

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize
php artisan queue:restart
php artisan up
```

## 8. Pre-launch checklist

- [ ] HTTPS enforced, `APP_DEBUG=false`, `APP_ENV=production`, secure session cookies
- [ ] Admin password changed after first login; `ADMIN_PASSWORD` removed from `.env`
- [ ] Live payment driver configured and tested end to end in the provider's test mode
- [ ] Queue worker and scheduler running (`php artisan schedule:list`)
- [ ] Test emails received for a booking, a quote and a payment
- [ ] Real prices, add-ons, tax rates and availability set in the admin
- [ ] Draft Terms, Privacy and Cancellation pages reviewed by a legal advisor and the DRAFT notice removed
- [ ] Business claims and testimonials confirmed as accurate
- [ ] `php artisan test` passes on the release build
- [ ] Old WordPress URLs redirect with 301 (`/recurring-cleaning`, `/product/<slug>`, `/enquiry`)
- [ ] `/sitemap.xml` submitted to Google Search Console

## 9. Backups

Back up the database daily, plus `storage/app/public` (uploaded service images). Test a restore before launch.
