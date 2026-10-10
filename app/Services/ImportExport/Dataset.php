<?php

namespace App\Services\ImportExport;

enum Dataset: string
{
    case Categories = 'categories';
    case Services = 'services';
    case Addons = 'addons';
    case Bookings = 'bookings';

    public function label(): string
    {
        return match ($this) {
            self::Categories => 'Categories',
            self::Services => 'Services',
            self::Addons => 'Add-ons',
            self::Bookings => 'Bookings',
        };
    }

    public function importable(): bool
    {
        return $this !== self::Bookings;
    }

    /**
     * Column names, in file order. The header row must match exactly.
     *
     * @return list<string>
     */
    public function headers(): array
    {
        return match ($this) {
            self::Categories => ['name', 'slug', 'parent_slug', 'description', 'seo_title', 'seo_description', 'sort_order', 'is_active'],
            self::Services => [
                'name', 'slug', 'category_slug', 'short_description', 'description', 'inclusions', 'exclusions',
                'instructions', 'pricing_method', 'price', 'hourly_rate', 'estimated_minutes', 'allows_recurring',
                'seo_title', 'seo_description', 'is_active', 'sort_order',
            ],
            self::Addons => ['name', 'slug', 'description', 'pricing_method', 'price', 'is_active', 'sort_order', 'service_slugs'],
            self::Bookings => [
                'reference', 'status', 'service', 'preferred_date', 'time_window', 'scheduled_at',
                'subtotal', 'discount', 'tax', 'total', 'currency',
                'contact_name', 'contact_email', 'contact_phone', 'address',
            ],
        };
    }

    /**
     * Short rules shown on the import/export page and implied by the blank template.
     */
    public function notes(): string
    {
        return match ($this) {
            self::Categories => 'Match parents with parent_slug. Rows can be in any order. Leave parent_slug blank for a top-level category. is_active is 1 or 0. Importing never deletes a category.',
            self::Services => 'category_slug must already exist. Separate inclusions and exclusions with |. Prices are dollars, such as 149.99. pricing_method is fixed, hourly, starting_quote, or custom_quote. Chat text and images are left unchanged. A previously deleted service is restored when its slug is imported again.',
            self::Addons => 'service_slugs lists every service this add-on belongs to, separated by |. An empty cell detaches it from all services. pricing_method is fixed, hourly, or quote. Prices are dollars. Quoted add-ons leave price blank.',
            self::Bookings => 'Export only. Filter by the date the booking was submitted and by status. Without a status, drafts are left out. Contact details and the service address are included.',
        };
    }
}
