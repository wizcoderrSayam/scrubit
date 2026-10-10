<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\CatalogCache;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = CatalogCache::remember('sitemap', function (): string {
            $urls = collect([
                ['loc' => route('home'), 'lastmod' => null],
                ['loc' => route('services.index'), 'lastmod' => null],
                ['loc' => route('about'), 'lastmod' => null],
                ['loc' => route('contact'), 'lastmod' => null],
                ['loc' => route('quote.create'), 'lastmod' => null],
                ['loc' => route('faq'), 'lastmod' => null],
            ]);

            ServiceCategory::query()->active()->ordered()->get()->each(function (ServiceCategory $category) use ($urls): void {
                $urls->push(['loc' => route('services.category', $category), 'lastmod' => $category->updated_at]);
            });

            Service::query()->active()->whereHas('category', fn ($query) => $query->active())->ordered()->get()
                ->each(function (Service $service) use ($urls): void {
                    $urls->push(['loc' => route('services.show', $service), 'lastmod' => $service->updated_at]);
                });

            Page::query()->published()->where('slug', '!=', 'about')->get()->each(function (Page $page) use ($urls): void {
                $urls->push(['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at]);
            });

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /account', 'Disallow: /book/', 'Disallow: /bookings/', 'Disallow: /demo-gateway/']
            : ['User-agent: *', 'Disallow: /'];

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
