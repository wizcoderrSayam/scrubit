<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Administrator
    |--------------------------------------------------------------------------
    |
    | Used by the AdminUserSeeder to create or update the first administrator.
    | The seeder is skipped when either value is missing.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Scrubit Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Setting Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults for every editable business setting. Values saved from the
    | admin dashboard are stored in the business_settings table and take
    | precedence. Only keys listed here may be stored.
    |
    */

    'settings' => [
        'business.name' => env('BUSINESS_NAME', 'Scrubit'),
        'business.tagline' => 'Professional Cleaning Services for Homes & Businesses',
        'business.phone' => env('BUSINESS_PHONE', '+1 (778) 962-6999'),
        'business.email' => env('BUSINESS_EMAIL', 'Info@scrubit.ca'),
        'business.location' => env('BUSINESS_LOCATION', 'Canada'),
        'business.currency' => env('BUSINESS_CURRENCY', 'CAD'),
        'brand.logo' => null,
        'brand.favicon' => null,
        'mail.host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'mail.port' => (int) env('MAIL_PORT', 587),
        'mail.username' => env('MAIL_USERNAME', ''),
        'mail.password' => '',
        'mail.from_address' => env('MAIL_FROM_ADDRESS', ''),
        'social.facebook' => null,
        'social.instagram' => null,
        'social.youtube' => null,
        'addon_note.text' => 'Starting {rate}/hr add on services',
        'addon_note.rate_cents' => 2500,
        'cancellation.cutoff_hours' => 48,
        'booking.min_lead_days' => 1,
        'booking.max_advance_days' => 90,
        'quote.default_expiry_days' => 7,
        'home.hero_eyebrow' => 'Reliable, insured and detail focused',
        'home.intro' => 'Scrubit is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses. We believe a clean space creates comfort, productivity, and peace of mind.',
        'home.cta_heading' => 'Ready for a Cleaner, Healthier Space?',
        'home.cta_text' => 'Relax while Scrubit provides trusted, eco-friendly cleaning services.',
        'home.stats.houses_cleaned' => 0,
        'home.stats.happy_customers' => 0,
        'home.stats.expert_cleaners' => 0,
        'home.stats.years_experience' => 0,
        'home.why_items' => [
            ['title' => 'Professional & Trained Staff', 'body' => 'Trained, insured, and background-checked.'],
            ['title' => 'Flexible & Customized Plans', 'body' => 'Cleaning services tailored to you.'],
            ['title' => 'Reliable, punctual & professional', 'body' => 'Consistent, on-time service you can trust.'],
            ['title' => 'Transparent, upfront pricing', 'body' => 'Affordable, upfront pricing with no hidden fees.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy WordPress URLs
    |--------------------------------------------------------------------------
    |
    | Service slugs that existed at the root of the old WordPress site and
    | must 301-redirect to /services/{slug}.
    |
    */

    'legacy_service_slugs' => [
        'recurring-cleaning',
        'one-time-cleaning',
        'home-cleaning',
        'custom-cleaning-plans',
        'janitorial-cleaning',
        'commercial-cleaning',
        'move-in-move-out-cleaning',
    ],

];
