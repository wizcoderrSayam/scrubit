<?php

namespace App\Providers;

use App\Http\Responses\HomeRedirectResponse;
use App\Models\User;
use App\Services\Chat\ChatResponder;
use App\Services\Chat\ScriptedResponder;
use App\Services\MailSettingsApplier;
use App\Services\SettingsService;
use App\View\Composers\BusinessComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->bind(ChatResponder::class, ScriptedResponder::class);
        $this->app->singleton(LoginResponse::class, HomeRedirectResponse::class);
        $this->app->singleton(VerifyEmailResponse::class, HomeRedirectResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        View::composer(['components.layouts.*', 'home', 'pages.*', 'services.*', 'bookings.*', 'checkout.*', 'booking.*', 'account.*'], BusinessComposer::class);

        RateLimiter::for('enquiries', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('chat', fn (Request $request) => Limit::perMinute(40)->by($request->ip()));
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(15)->by($request->user()?->getKey() ?? $request->ip()));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));

        $this->app->make(MailSettingsApplier::class)->apply();
    }
}
