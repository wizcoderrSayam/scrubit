# Scrubit: Architecture & Implementation Plan

Laravel 13 monolith · PHP 8.4 · MySQL · Blade + Tailwind 4 + Alpine · Vite 8 · database queue · PHPUnit.
Content facts come from [SITE-INVENTORY.md](SITE-INVENTORY.md).

## 1. Decisions

| Area | Decision |
|---|---|
| Pricing | Engine supports `fixed`, `hourly`, `starting_quote`, `custom_quote` + priced add-ons. All 7 services are seeded with `pricing_method = custom_quote` (no real prices exist). The add-on catalog is seeded empty. |
| Payment | Full approved amount upfront. `PaymentGateway` interface; **`demo`** driver now, `stripe` (Checkout) driver later. Selected by `PAYMENT_DRIVER`. |
| Currency / tax | CAD default. Tax rates admin-managed (`tax_rates`); seed editable "GST" 5%. |
| Auth | Laravel Fortify + custom Blade views. Email verification for accounts. |
| Roles | `users.role` ∈ {`admin`, `customer`}. `Gate::define('admin')`. Policies per model. |
| Guest access | Unguessable `reference` + secret access token (SHA-256 hash stored) in emailed links. Owners are authorised by `user_id`. |
| Scheduling | Preferred date + time window; weekday rules, blackout dates, minimum lead time. Admin confirms the exact time. No capacity. |
| Recurring | Booking records frequency; quote/payment covers the first visit; admin "Repeat booking". |
| Chat | Guided, rule-based per-service script behind a `ChatResponder` interface. All messages persisted. |
| Add-ons | None seeded. Extra tasks mentioned in chat become `needs_quote` flags, never auto-priced. |
| Add-on note | Setting `addon_note.text` = "Starting $25/hr add on services", `addon_note.rate_cents` = 2500. |
| Cancellation | Unpaid bookings: customer cancels instantly. Paid bookings: customer requests → admin approves/rejects + records refund. Cut-off `cancellation.cutoff_hours`. |
| Admin UI | Custom Blade + Alpine. |
| Money | Integer minor units (`*_cents`, `unsignedBigInteger`). `App\Support\Money` value object. Tax in basis points (500 = 5%): for each active rate, `tax = round_half_up(taxable_subtotal × bps / 10000)` computed once on the booking/quote subtotal (not per line), then rates summed. No floats anywhere. |

## 2. Database schema

Conventions: `id` bigint PK, timestamps on every table, FKs `constrained()`; `cascadeOnDelete` only for owned children, `restrictOnDelete` for references from bookings and payments.

### Identity
- **users**: name, email (unique), phone nullable, `role` (string enum, index), password, email_verified_at, remember_token.
- **customer_addresses**: `user_id` → users (cascade); label, line1, line2, city, province, postal_code, country (default `CA`), notes, is_default.

### Catalog
- **service_categories**: name, `slug` (unique), description, sort_order, is_active.
- **services**: `service_category_id` → service_categories (restrict); name, `slug` (unique), short_description, description (text), inclusions JSON, exclusions JSON, instructions text, featured_image path, `pricing_method` enum, price_cents nullable, hourly_rate_cents nullable, min_hours nullable, estimated_minutes nullable, requires_quote bool, allows_recurring bool, recurring_frequencies JSON, chat_script JSON (greeting, suggested prompts, step prompts), seo_title, seo_description, is_active (index), sort_order, soft deletes.
- **service_images**: `service_id` → services (cascade); path, alt, sort_order.
- **service_addons**: name, slug (unique), description, `pricing_method` ∈ {fixed, hourly, quote}, price_cents nullable, is_active.
- **service_service_addon** (pivot): `service_id` (cascade), `service_addon_id` (cascade); unique(service_id, service_addon_id).

### Booking
- **bookings**:
  - Identity: `reference` (unique, `SCR-` + 10 Crockford base32 chars), `access_token_hash` (char 64), `draft_token_hash` (char 64, index, nullable; grants draft access only and is cleared on submit or when claimed by a user).
  - Links: `user_id` → users nullable (null on delete), `service_id` → services (restrict).
  - Status: `status` enum (index).
  - Snapshots: `service_name`, `pricing_method`, `unit_price_cents`, `hourly_rate_cents`, `pricing_terms` JSON.
  - Scheduling: `preferred_date` date (index), `time_window` enum, `scheduled_at` datetime nullable, `frequency` enum nullable.
  - Contact: `contact_name`, `contact_email` (index), `contact_phone`.
  - Money: `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents`, `currency` char 3, `tax_breakdown` JSON.
  - Workflow: `chat_state` JSON (filled slots + current step), `terms_accepted_at`, `submitted_at`, `confirmed_at`, `completed_at`, `cancelled_at`, `cancellation_reason`, `repeated_from_id` → bookings nullable.
- **booking_addresses**: `booking_id` → bookings (cascade), unique; same address columns as customer_addresses (snapshot).
- **booking_addons**: `booking_id` (cascade), `service_addon_id` nullable (null on delete); name, description, pricing_method, unit_price_cents nullable, quantity, line_total_cents nullable, needs_quote bool, source ∈ {selected, chat_flag, admin}.
- **booking_messages**: `booking_id` (cascade), `user_id` nullable; `sender` ∈ {customer, assistant, admin, system}; `type` ∈ {text, widget, flag, internal_note}; body text; meta JSON; created_at (index with booking_id). `internal_note` is never shown to customers.
- **booking_status_histories**: `booking_id` (cascade), from_status nullable, to_status, `user_id` nullable (actor), reason, created_at.

### Quotes & payments
- **booking_quotes**: `booking_id` (cascade); subtotal_cents, tax_cents, total_cents, currency, description, `status` ∈ {draft, sent, accepted, expired, superseded}, expires_at, sent_at, accepted_at, `created_by` → users.
- **payments**:
  - Links: `booking_id` → bookings (restrict), `booking_quote_id` nullable.
  - Provider: `provider` string, `provider_ref` (session or intent id, nullable; **unique(provider, provider_ref)**), `checkout_url`, `checkout_expires_at`, `idempotency_key` (unique, uuid).
  - Money and status: amount_cents, currency, `status` enum (index), refunded_cents default 0, failure_reason.
  - Other: meta JSON (no card data), paid_at.
  - **`success_guard` nullable unique**: set to `booking_id` on success, so a booking can never have two succeeded payments.
- **payment_events**: provider, `event_id` with **unique(provider, event_id)**; type, `payment_id` nullable, payload JSON, processed_at, error.

### Availability & settings
- **availability_rules**: weekday (0–6, unique), is_open, windows JSON (subset of morning/afternoon/evening).
- **availability_exceptions**: `date` (unique), is_closed, reason.
- **business_settings**: `key` (unique), value JSON. Cached via `SettingsService`, busted on save.
- **tax_rates**: name, rate_bps, is_active, sort_order.
- **pages**: slug (unique), title, body (sanitised HTML), seo_title, seo_description, is_published.
- **faqs**: question, answer, sort_order, is_published.
- **testimonials**: author_name, body, service_id nullable, sort_order, is_published.
- **email_templates**: `key` (unique), subject, body (Markdown with `{{placeholder}}` tokens), is_active.
- Framework tables: `notifications`, `jobs`, `failed_jobs`, `cache`, `sessions`.

### Relationship summary
```
ServiceCategory 1─* Service *─* ServiceAddon
Service 1─* ServiceImage · Service 1─* Booking · Service 0..1─* Testimonial
User 1─* CustomerAddress · User 0..1─* Booking
Booking 1─1 BookingAddress · 1─* BookingAddon · 1─* BookingMessage
Booking 1─* BookingQuote · 1─* Payment · 1─* BookingStatusHistory
BookingQuote 1─* Payment · Payment 1─* PaymentEvent
```

## 3. State machines

Each machine is backed by an enum with an `allowedTransitions()` map. `BookingStateMachine::transition()` runs inside a DB transaction with `lockForUpdate()`, writes `booking_status_histories`, and dispatches `BookingStatusChanged`. An invalid transition throws `InvalidBookingTransition`.

### Booking
```
draft ──submit (quote needed)──▶ pending_quote ──quote sent──▶ awaiting_quote_payment ──paid──▶ confirmed
draft ──submit (priced)────────▶ awaiting_payment ─────────────────────────────paid──▶ confirmed
awaiting_quote_payment ──quote expired / requote──▶ pending_quote
confirmed ──▶ in_progress ──▶ completed
pending_quote | awaiting_payment | awaiting_quote_payment ──cancel──▶ cancelled
confirmed ──request──▶ cancellation_requested ──approve──▶ cancelled
cancellation_requested ──reject──▶ confirmed
confirmed (admin) ──▶ cancelled
```
Drafts are never cancelled. Drafts untouched for 30 days are pruned by the scheduler.

### Quote
`draft → sent → accepted`. `sent|accepted → expired` (scheduled command, booking → pending_quote). **The expiry command skips any booking with a `pending`/`processing` payment**, and a checkout session is never opened with an expiry later than the quote's `expires_at`. `draft|sent|expired → superseded` when a new quote is issued. Only one non-superseded quote per booking.

### Payment
`pending → processing | succeeded | failed | cancelled` · `processing → succeeded | failed` · `succeeded → partially_refunded | refunded` · `partially_refunded → refunded`.
A failed or cancelled payment never changes the booking's status; the customer can retry, which creates a new Payment.

**Late or orphaned success.** If a success event arrives while the booking is not payable (`cancelled`, or `pending_quote` after an expiry), or for a quote that is no longer current, the payment is still recorded as `succeeded` (money was taken). The booking is **not** transitioned. Instead `meta.needs_refund = true` is set, an admin alert is raised, and it appears in a "Payments needing attention" list where the admin either refunds it or re-confirms the booking.

## 4. Service layer (`app/Services`)

| Class | Responsibility |
|---|---|
| `BookingDraftService` | `startOrResume(Service, Request)`: finds the draft via the cookie token (or the user's draft); `claimForUser(User)` on login (sets `user_id`, clears `draft_token_hash`); prunes stale drafts. |
| `Chat\ChatService` | `post(Booking, string $text)` / `submitWidget(Booking, step, data)` → persists the message, asks the responder, stores the reply, updates `chat_state`. |
| `Chat\ChatResponder` (interface) → `ScriptedResponder` | `respond(Booking, ChatState, Message): Reply`. Steps: needs → property → schedule → address → contact. `isComplete(ChatState)`. A future `AiResponder` uses the same contract. |
| `BookingPricingService` | `calculate(Booking): PriceBreakdown` from DB values only. Returns `quoteRequired=true` when the service is custom/starting-quote or any addon `needs_quote`. |
| `BookingService` | `submit(Booking)`: validates completeness and terms, snapshots, issues reference and token, transitions; `repeat(Booking)`. |
| `QuoteService` | `draft`, `send` (supersedes the old quote, booking → awaiting_quote_payment, notifies), `accept`, `expireDue()`. |
| `Payments\PaymentGateway` (interface) | `createCheckout(Payment): CheckoutRedirect`, `parseWebhook(Request): GatewayEvent` (verifies the signature), `fetchStatus(Payment)`, `refund(Payment, cents)`. |
| `Payments\DemoGateway` | Hosted fake checkout page (`/demo-gateway/{payment:idempotency_key}`, also gated by `BookingAccess` via owner, draft cookie or access token) with Succeed / Fail / Cancel / Pending buttons that POST a timestamped HMAC-SHA256-signed webhook (`DEMO_WEBHOOK_SECRET`, 5-min replay window). Enabled only when `PAYMENT_DRIVER=demo`. **Refuses to boot when `APP_ENV=production`** unless `DEMO_GATEWAY_ALLOW_PRODUCTION=true`. |
| `PaymentService` | `startCheckout(Booking)`: locks the booking (`lockForUpdate`); at most one open (`pending`/`processing`) payment per booking. A `pending` payment whose checkout session is still valid is reused (same URL); an expired one is marked `cancelled` and a fresh Payment + session is created. A `processing` payment blocks a new checkout ("payment in progress"). `handleEvent(GatewayEvent)` locks the payment and booking, applies the payment transition, and confirms the booking only if it is still payable and the payment's quote is current (otherwise: late-success handling, §3). |
| `WebhookProcessor` | Verify → `insertOrIgnore` into `payment_events` (duplicate → 200 no-op) → `PaymentService::handleEvent` in a transaction. |
| `AvailabilityService` | `isBookable(date, window)`, `nextAvailableDates(n)`. |
| `SettingsService` | Typed get/set with cache. |
| `BookingAccess` | `canView(?User, Booking, ?token)` with `hash_equals`. Used by `BookingPolicy` and the `booking.access` middleware. |

The return URL never marks a booking paid. The confirmation page shows "processing" until the webhook (or `fetchStatus`) confirms payment.

## 5. Route map

```
GET  /                                   home
GET  /about  /contact  /faq  /request-a-quote  /pages/{page:slug}
POST /contact  /request-a-quote          throttle:quote
GET  /services                           services.index
GET  /services/category/{category:slug}  services.category
GET  /services/{service:slug}            services.show
GET  /sitemap.xml  /robots.txt

GET  /book/{service:slug}/customize      booking.customize (creates/resumes draft)
POST /book/{booking:reference}/messages  booking.messages.store   (JSON, throttle)
POST /book/{booking:reference}/widgets/{step}                    (JSON)
GET  /book/{booking:reference}/review    booking.review
PATCH /book/{booking:reference}/review   (edit contact/address/schedule)
POST /book/{booking:reference}/submit    booking.submit
POST /book/{booking:reference}/pay       checkout.start
GET  /book/{booking:reference}/payment/return|cancel   checkout.return
GET  /bookings/{booking:reference}       bookings.show       (owner or ?token)
GET  /bookings/{booking:reference}/print
POST /bookings/{booking:reference}/quote/accept
POST /bookings/{booking:reference}/cancel
GET|POST /demo-gateway/{payment:idempotency_key}   (demo driver only)
POST /webhooks/payments/{provider}       (CSRF-exempt, throttled)

auth+verified  /account, /account/profile, /account/addresses (resource), /account/bookings
auth+can:admin /admin
  dashboard · services · categories · addons (resource) · services/{s}/images
  bookings (index/show/update) + /status, /notes, /repeat · quotes (index/create/send)
  payments (index/show/refund) · customers (index/show/update)
  content: home, faqs, testimonials, pages, email-templates
  settings: business, tax, payment, availability, addon-note, cancellation
```
Draft pages use the reference plus the draft cookie, so a draft is only reachable from the browser (or account) that created it. Contact, address and schedule are editable only while the booking is a `draft`; after submit, only admins can change them.

## 6. Security checklist
CSRF on all web forms (webhooks excepted) · Form Requests everywhere, with `$fillable` allow-lists · Policies on every admin and customer route · `throttle` on login, register, quote/contact, chat, webhooks · image uploads validated with `image|mimes:jpg,png,webp|max:4096` and re-encoded · page/email bodies sanitised on save, Blade `{{ }}` escaping · secrets only in `.env` · logs redact email, phone and tokens · `SESSION_SECURE_COOKIE`, `http_only`, `same_site=lax` · numeric IDs never in public URLs.

## 7. Phases

| # | Phase | Exit criteria |
|---|---|---|
| 1 | Inspection & planning | This doc + site inventory. |
| 2 | Foundation | Boost, Fortify, Alpine installed; layouts; brand theme; roles/gate; settings; admin seeder; auth tests green. |
| 3 | Catalog | Category/service/addon/image CRUD; dynamic service pages; SEO, sitemap; seeded 7 services. |
| 4 | Booking engine | Drafts, guided chat, availability, review/edit, pricing service, submit → pending_quote; tests. |
| 5 | Payments & quotes | Demo gateway, webhooks, idempotency, retries, quotes send/accept/expire, confirmation/print; tests. |
| 6 | Admin & notifications | Booking/customer management, dashboard metrics, status history, queued emails from templates. |
| 7 | Testing & deploy | Full suite, responsive QA, legacy redirects, `docs/DEPLOYMENT.md`. |

## 8. Test matrix (target)
Pricing (each method, add-ons, tax rounding, tampered input ignored) · draft persistence + claim on login · chat gating · booking/quote/payment transitions (valid + invalid) · quote expiry command · demo payment success/fail/cancel/pending · duplicate webhook → one payment, one email · success after cancel or quote expiry → booking unchanged + needs_refund flag · quote expiry skipped while payment open · demo gateway blocked in production · bad signature → 400 · success URL alone ≠ paid · guest token vs. guessed reference → 404 · customer vs. admin route authorisation.
