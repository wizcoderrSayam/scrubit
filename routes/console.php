<?php

use App\Services\BookingDraftService;
use App\Services\Payments\PaymentService;
use App\Services\QuoteService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quotes:expire', function (QuoteService $quotes) {
    $this->info("Expired {$quotes->expireDue()} quote(s).");
})->purpose('Expire overdue quotes and return their bookings to the quote queue');

Artisan::command('bookings:prune-drafts {--days=30}', function (BookingDraftService $drafts) {
    $this->info("Deleted {$drafts->pruneStaleDrafts((int) $this->option('days'))} abandoned draft(s).");
})->purpose('Delete booking drafts that have not been touched recently');

Artisan::command('payments:expire-checkouts', function (PaymentService $payments) {
    $this->info("Cancelled {$payments->expireStaleCheckouts()} abandoned checkout(s).");
})->purpose('Cancel pending payments whose checkout session has expired');

Schedule::command('quotes:expire')->hourly()->withoutOverlapping();
Schedule::command('payments:expire-checkouts')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('bookings:prune-drafts')->daily()->withoutOverlapping();
