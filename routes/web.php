<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\ChatController;
use App\Http\Controllers\Booking\CheckoutController;
use App\Http\Controllers\Booking\CustomizeController;
use App\Http\Controllers\Booking\DemoGatewayController;
use App\Http\Controllers\Booking\ReviewController;
use App\Http\Controllers\Booking\StartBookingController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\BookingHistoryController;
use App\Http\Controllers\Public\EnquiryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SeoController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/contact', [EnquiryController::class, 'contact'])->name('contact');
Route::post('/contact', [EnquiryController::class, 'storeContact'])->middleware('throttle:enquiries')->name('contact.store');
Route::get('/request-a-quote', [EnquiryController::class, 'quote'])->name('quote.create');
Route::post('/request-a-quote', [EnquiryController::class, 'storeQuote'])->middleware('throttle:enquiries')->name('quote.store');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/category/{category:slug}', [ServiceController::class, 'category'])->name('services.category');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Booking flow: Select → Customize → Review → Payment → Confirmation
|--------------------------------------------------------------------------
*/

Route::get('/book/{service:slug}', StartBookingController::class)->name('booking.start');

Route::prefix('/book/{booking:reference}')->group(function () {
    Route::get('/customize', CustomizeController::class)->name('booking.customize');
    Route::post('/messages', [ChatController::class, 'message'])->middleware('throttle:chat')->name('booking.messages.store');
    Route::post('/steps/{step}', [ChatController::class, 'step'])->middleware('throttle:chat')
        ->whereIn('step', ['schedule', 'details', 'extras'])->name('booking.steps.store');
    Route::get('/review', [ReviewController::class, 'show'])->name('booking.review');
    Route::post('/submit', [ReviewController::class, 'submit'])->middleware('throttle:sensitive')->name('booking.submit');

    Route::get('/pay', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/pay', [CheckoutController::class, 'start'])->middleware('throttle:sensitive')->name('checkout.start');
    Route::get('/payment/return', [CheckoutController::class, 'return'])->name('checkout.return');
    Route::get('/payment/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
});

Route::prefix('/bookings/{booking:reference}')->group(function () {
    Route::get('/', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/print', [BookingController::class, 'print'])->name('bookings.print');
    Route::post('/quote/accept', [BookingController::class, 'acceptQuote'])->middleware('throttle:sensitive')->name('bookings.quote.accept');
    Route::post('/quote/request-new', [BookingController::class, 'requestRequote'])->middleware('throttle:sensitive')->name('bookings.quote.requote');
    Route::post('/cancel', [BookingController::class, 'cancel'])->middleware('throttle:sensitive')->name('bookings.cancel');
});

Route::get('/demo-gateway/{payment:idempotency_key}', [DemoGatewayController::class, 'show'])->name('demo-gateway.show');
Route::post('/demo-gateway/{payment:idempotency_key}', [DemoGatewayController::class, 'complete'])->middleware('throttle:sensitive')->name('demo-gateway.complete');

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.payments');

/*
|--------------------------------------------------------------------------
| Customer account
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('account')->group(function () {
    Route::get('/', AccountController::class)->name('account');
    Route::get('/bookings', BookingHistoryController::class)->name('account.bookings');
    Route::get('/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'can:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('services', Admin\ServiceController::class)->except('show');
        Route::post('/services/{service}/toggle', [Admin\ServiceController::class, 'toggle'])->name('services.toggle');
        Route::post('/services/{service}/images', [Admin\ServiceImageController::class, 'store'])->name('services.images.store');
        Route::delete('/services/{service}/images/{image}', [Admin\ServiceImageController::class, 'destroy'])->name('services.images.destroy');
        Route::resource('categories', Admin\ServiceCategoryController::class)->except('show');
        Route::resource('addons', Admin\ServiceAddonController::class)->except('show');

        Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::put('/bookings/{booking}', [Admin\BookingController::class, 'update'])->name('bookings.update');
        Route::post('/bookings/{booking}/status', [Admin\BookingController::class, 'transition'])->name('bookings.transition');
        Route::post('/bookings/{booking}/notes', [Admin\BookingController::class, 'note'])->name('bookings.notes.store');
        Route::post('/bookings/{booking}/repeat', [Admin\BookingController::class, 'repeat'])->name('bookings.repeat');
        Route::post('/bookings/{booking}/quotes', [Admin\QuoteController::class, 'store'])->name('bookings.quotes.store');

        Route::get('/quotes', [Admin\QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/refund', [Admin\PaymentController::class, 'refund'])->name('payments.refund');
        Route::post('/payments/{payment}/resolve', [Admin\PaymentController::class, 'resolve'])->name('payments.resolve');

        Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');
        Route::put('/customers/{customer}', [Admin\CustomerController::class, 'update'])->name('customers.update');

        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::post('/enquiries/{enquiry}/handle', [Admin\EnquiryController::class, 'handle'])->name('enquiries.handle');

        Route::get('/content/home', [Admin\HomeContentController::class, 'edit'])->name('content.home.edit');
        Route::put('/content/home', [Admin\HomeContentController::class, 'update'])->name('content.home.update');
        Route::get('/pages', [Admin\PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/{page}/edit', [Admin\PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
        Route::resource('faqs', Admin\FaqController::class)->except('show');
        Route::resource('testimonials', Admin\TestimonialController::class)->except('show');
        Route::get('/email-templates', [Admin\EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{emailTemplate}/edit', [Admin\EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{emailTemplate}', [Admin\EmailTemplateController::class, 'update'])->name('email-templates.update');
        Route::post('/email-templates/{emailTemplate}/reset', [Admin\EmailTemplateController::class, 'reset'])->name('email-templates.reset');

        Route::get('/settings/business', [Admin\BusinessSettingsController::class, 'edit'])->name('settings.business.edit');
        Route::put('/settings/business', [Admin\BusinessSettingsController::class, 'update'])->name('settings.business.update');
        Route::post('/settings/test-email', [Admin\BusinessSettingsController::class, 'sendTestEmail'])->middleware('throttle:sensitive')->name('settings.test-email');
        Route::get('/settings/tax', [Admin\TaxRateController::class, 'index'])->name('settings.tax.index');
        Route::post('/settings/tax', [Admin\TaxRateController::class, 'store'])->name('settings.tax.store');
        Route::put('/settings/tax/{taxRate}', [Admin\TaxRateController::class, 'update'])->name('settings.tax.update');
        Route::delete('/settings/tax/{taxRate}', [Admin\TaxRateController::class, 'destroy'])->name('settings.tax.destroy');
        Route::get('/settings/availability', [Admin\AvailabilityController::class, 'edit'])->name('settings.availability.edit');
        Route::put('/settings/availability', [Admin\AvailabilityController::class, 'update'])->name('settings.availability.update');
        Route::post('/settings/availability/closed-dates', [Admin\AvailabilityController::class, 'storeException'])->name('settings.availability.exceptions.store');
        Route::delete('/settings/availability/closed-dates/{exception}', [Admin\AvailabilityController::class, 'destroyException'])->name('settings.availability.exceptions.destroy');
        Route::get('/settings/payments', Admin\PaymentSettingsController::class)->name('settings.payments');
    });

/*
|--------------------------------------------------------------------------
| Legacy WordPress URLs
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('/enquiry', '/request-a-quote');
Route::get('/product/{slug}', fn (string $slug) => redirect()->route('services.show', $slug, 301))->where('slug', '[a-z0-9-]+');
Route::get('/{legacySlug}', fn (string $legacySlug) => redirect()->route('services.show', $legacySlug, 301))
    ->whereIn('legacySlug', config('scrubit.legacy_service_slugs'));
