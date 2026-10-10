<?php

namespace Database\Seeders;

use App\Enums\ServicePricingMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ImageUploader;
use Illuminate\Database\Seeder;

/**
 * The seven services verified on scrubit.ca (see docs/SITE-INVENTORY.md).
 * All are quote-first because the live site publishes no real prices.
 * Safe to re-run: existing records are matched by slug and admin edits to them are kept.
 */
class ServiceCatalogSeeder extends Seeder
{
    public function run(ImageUploader $images): void
    {
        $residential = ServiceCategory::query()->firstOrCreate(['slug' => 'residential'], [
            'name' => 'Residential',
            'description' => 'Cleaning services for homes, apartments and condos.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $commercial = ServiceCategory::query()->firstOrCreate(['slug' => 'commercial'], [
            'name' => 'Commercial',
            'description' => 'Cleaning services for offices, retail and business facilities.',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        foreach ($this->services($residential, $commercial) as $order => $attributes) {
            $service = Service::withTrashed()->where('slug', $attributes['slug'])->first();

            if ($service === null) {
                $service = Service::create([...$attributes, 'sort_order' => $order + 1, 'is_active' => true]);
            }

            $asset = database_path("seeders/assets/services/{$service->slug}.webp");

            if ($service->featured_image === null && is_file($asset)) {
                $service->forceFill(['featured_image' => $images->storeFromPath($asset)])->save();
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(ServiceCategory $residential, ServiceCategory $commercial): array
    {
        $quote = ServicePricingMethod::CustomQuote;

        return [
            [
                'service_category_id' => $residential->id,
                'name' => 'Recurring Cleaning',
                'slug' => 'recurring-cleaning',
                'short_description' => 'Scheduled cleaning to keep your space consistently spotless',
                'description' => "## Maintain a spotless home without the stress\n\nScheduled professional cleaning that addresses every detail for a pristine finish. Choose the frequency that suits your household:\n\n- **Weekly:** ideal for busy households\n- **Bi-weekly:** a balanced maintenance option\n- **Monthly:** lighter upkeep",
                'inclusions' => [
                    'Dusting of furniture and decor',
                    'Vacuuming and microfiber mopping of floors',
                    'Disinfection of high-touch surfaces (handles, switches)',
                    'Kitchen countertop, sink and appliance exterior polishing',
                    'Bathroom basin, shower and toilet scrubbing',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => true,
                'chat_script' => [
                    'greeting' => 'Hi! Let us set up your recurring cleaning. Tell us about your home and how you would like it maintained.',
                    'needs_prompt' => 'What are your cleaning priorities for each visit? For example the kitchen, bathrooms, floors or dusting.',
                    'suggested_prompts' => [
                        'Bi-weekly cleaning focused on the kitchen and bathrooms',
                        'Weekly upkeep for a busy family home with pets',
                        'Monthly light cleaning, mainly dusting and floors',
                    ],
                ],
                'seo_title' => 'Recurring Cleaning Services',
                'seo_description' => 'Weekly, bi-weekly or monthly recurring home cleaning by Scrubit. Describe your priorities and get a personalized quote.',
            ],
            [
                'service_category_id' => $residential->id,
                'name' => 'One Time Cleaning',
                'slug' => 'one-time-cleaning',
                'short_description' => 'Thorough one-time cleaning for instant cleanliness boost',
                'description' => "## A Fresh Start for Your Space\n\nA high-intensity service that restores your space in a single visit, ideal for event preparation or a seasonal refresh, with meticulous attention to every corner.\n\nPopular for special occasions, seasonal cleaning, post-renovation cleanup, pre-sale home preparation and refreshing commercial spaces.",
                'inclusions' => [
                    'Intensive dusting',
                    'Deep floor cleaning',
                    'Grease removal',
                    'Scrubbing',
                    'Shine: glass, mirrors and stainless steel polishing',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => false,
                'chat_script' => [
                    'greeting' => 'Planning a one-time deep clean? Tell us what it is for and what needs the most attention.',
                    'needs_prompt' => 'What is the occasion, and which areas need the most attention?',
                    'suggested_prompts' => [
                        'Deep clean before hosting a family event',
                        'Seasonal refresh with extra focus on grease in the kitchen',
                        'Post-renovation dust cleanup',
                    ],
                ],
                'seo_title' => 'One-Time Deep Cleaning',
                'seo_description' => 'One-time deep cleaning for events, seasonal refreshes and post-renovation cleanups. Get a personalized quote from Scrubit.',
            ],
            [
                'service_category_id' => $residential->id,
                'name' => 'Home Cleaning',
                'slug' => 'home-cleaning',
                'short_description' => 'Reliable home cleaning for everyday comfort and hygiene',
                'description' => "## Keep your living space healthy and inviting\n\nThis essential service provides a high-impact refresh for your entire home, ensuring every room is meticulously addressed for a pristine finish.",
                'inclusions' => [
                    'Surface care: dusting furniture, shelves and decor',
                    'Floor refresh: vacuuming and sanitized mopping',
                    'Kitchen clean: countertops, sinks and exterior appliances',
                    'Bathroom sanitization: toilets, showers and basins',
                    'Living areas: tidying common spaces and polishing',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => true,
                'chat_script' => [
                    'greeting' => 'Hi! Tell us about your home and what would make it feel fresh and comfortable again.',
                    'needs_prompt' => 'Which rooms should we focus on, and is there anything that needs extra care?',
                    'suggested_prompts' => [
                        'Full home refresh: kitchen, bathrooms, living areas and floors',
                        'Focus on sanitizing the bathrooms and kitchen',
                        'Dusting and floors throughout, we have pets',
                    ],
                ],
                'seo_title' => 'Home Cleaning Services',
                'seo_description' => 'Reliable home cleaning for kitchens, bathrooms, floors and living areas. Customize your clean and get a quote from Scrubit.',
            ],
            [
                'service_category_id' => $residential->id,
                'name' => 'Custom Cleaning Plans',
                'slug' => 'custom-cleaning-plans',
                'short_description' => 'Personalized cleaning plans designed to perfectly match your needs',
                'description' => "## Tailored to Your Lifestyle\n\nA flexible service that lets you prioritize specific tasks or rooms, ensuring your top priorities are meticulously addressed for a pristine finish.\n\nGreat for homes with pets or children, busy households, offices with unique requirements, and medical, retail or professional environments.",
                'inclusions' => [
                    'Zoned cleaning of designated rooms only',
                    'Task rotation: you choose which chores each visit',
                    'Flexible scope based on your budget',
                    'Special requests such as laundry or interior windows',
                    'Targeted detail for frequently used spaces',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => true,
                'chat_script' => [
                    'greeting' => 'Let us build a cleaning plan around your priorities. Tell us exactly what you want included.',
                    'needs_prompt' => 'Which rooms or tasks should your plan include, and is there anything we should skip?',
                    'suggested_prompts' => [
                        'Only the kitchen and two bathrooms each visit',
                        'Rotate tasks: floors one visit, windows and baseboards the next',
                        'Add laundry and interior windows to a standard clean',
                    ],
                ],
                'seo_title' => 'Custom Cleaning Plans',
                'seo_description' => 'Build a cleaning plan around your priorities with zoned cleaning, task rotation and special requests. Quote from Scrubit.',
            ],
            [
                'service_category_id' => $commercial->id,
                'name' => 'Janitorial Cleaning',
                'slug' => 'janitorial-cleaning',
                'short_description' => 'Professional janitorial cleaning for offices and commercial spaces',
                'description' => "## Maintain a productive and hygienic business environment\n\nOur reliable janitorial service provides consistent, high-standard maintenance for your facility, ensuring every area is meticulously addressed for a pristine finish. Daily, nightly or weekly scheduling is available.\n\nSuitable for office buildings, medical and healthcare facilities, schools, retail stores, warehouses and property management buildings.",
                'inclusions' => [
                    'Workstations: dusting and sanitization of desks, phones and touchpoints',
                    'Floor maintenance: vacuuming and hard-floor care',
                    'Restroom hygiene: disinfection and restocking of essential supplies',
                    'Common areas: breakrooms, lobbies and high-traffic entryways',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => true,
                'chat_script' => [
                    'greeting' => 'Hello! Tell us about your facility and the janitorial routine you need.',
                    'needs_prompt' => 'What type of facility is it, and which areas need regular attention (workstations, restrooms, breakrooms, entrances)?',
                    'suggested_prompts' => [
                        'Nightly office cleaning for 20 workstations and 2 restrooms',
                        'Weekly cleaning for a retail store, including restocking restroom supplies',
                        'Daily common-area and breakroom cleaning',
                    ],
                ],
                'seo_title' => 'Janitorial Cleaning Services',
                'seo_description' => 'Daily, nightly or weekly janitorial cleaning for offices, retail, schools and facilities. Request a tailored quote from Scrubit.',
            ],
            [
                'service_category_id' => $commercial->id,
                'name' => 'Commercial Cleaning',
                'slug' => 'commercial-cleaning',
                'short_description' => 'Professional commercial cleaning for spotless business environments',
                'description' => "## Business Excellence\n\nElevate your professional image with a spotless facility. This comprehensive service targets high-traffic environments to support a healthy and impressive workspace, with daytime, evening or overnight scheduling.",
                'inclusions' => [
                    'Broad surface disinfection of shared tables, counters and reception areas',
                    'Commercial floor care: vacuuming and industrial-grade mopping',
                    'Window and glass: entrance doors and partitions',
                    'Kitchens and breakrooms: deep cleaning of shared food prep areas',
                    'Aesthetic polishing of metal fixtures and decor',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => true,
                'chat_script' => [
                    'greeting' => 'Hello! Tell us about your business space and what a spotless result looks like for you.',
                    'needs_prompt' => 'What kind of space is it, roughly how large, and which areas see the most traffic?',
                    'suggested_prompts' => [
                        'Deep clean of reception, breakroom and glass partitions',
                        'Evening cleaning for a 3,000 sq ft office',
                        'High-traffic entrance and floor care',
                    ],
                ],
                'seo_title' => 'Commercial Cleaning Services',
                'seo_description' => 'Commercial cleaning for offices and business spaces: surfaces, floors, glass and breakrooms. Get a quote from Scrubit.',
            ],
            [
                'service_category_id' => $residential->id,
                'name' => 'Move-In / Move Out Cleaning',
                'slug' => 'move-in-move-out-cleaning',
                'short_description' => 'Top-to-bottom cleaning of empty spaces for seamless moves',
                'description' => "## Seamless Transitions\n\nA top-to-bottom scrub of empty spaces, ensuring every surface is meticulously addressed for a pristine finish. Helpful for lease or rental inspections, preparing a property for sale, or getting a new home ready for occupancy.",
                'inclusions' => [
                    'Interior closet, shelf and drawer dusting',
                    'Baseboards and door frames hand-wiped',
                    'Exterior appliance degreasing (ovens, fridges, dishwashers)',
                    'Deep disinfection of bathrooms and kitchen',
                    'Debris and packing material removal',
                ],
                'pricing_method' => $quote,
                'allows_recurring' => false,
                'chat_script' => [
                    'greeting' => 'Moving soon? Tell us about the property and we will make it spotless for the handover.',
                    'needs_prompt' => 'Are you moving in or out, what condition is the property in, and is it empty?',
                    'suggested_prompts' => [
                        'Move-out clean for a 2 bedroom apartment before the inspection',
                        'Move-in clean before we unpack, the place is empty',
                        'Property prep before listing for sale',
                    ],
                ],
                'seo_title' => 'Move-In / Move-Out Cleaning',
                'seo_description' => 'Top-to-bottom move-in and move-out cleaning for rentals, sales and new homes. Describe the property and get a quote.',
            ],
        ];
    }
}
