<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Testimonial;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        return view('pages.show', ['page' => $page]);
    }

    public function about(): View
    {
        $page = Page::query()->published()->where('slug', 'about')->firstOrFail();

        return view('pages.about', [
            'page' => $page,
            'testimonials' => Testimonial::query()->published()->get(),
        ]);
    }

    public function faq(): View
    {
        return view('pages.faq', ['faqs' => Faq::query()->published()->get()]);
    }
}
