<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeContentController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function edit(): View
    {
        $whyItems = collect((array) $this->settings->get('home.why_items', []))
            ->map(fn (array $item) => trim(($item['title'] ?? '').' | '.($item['body'] ?? '')))
            ->implode("\n");

        return view('admin.content.home', ['settings' => $this->settings->all(), 'whyItems' => $whyItems]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_eyebrow' => ['required', 'string', 'max:120'],
            'intro' => ['required', 'string', 'max:1000'],
            'cta_heading' => ['required', 'string', 'max:160'],
            'cta_text' => ['required', 'string', 'max:300'],
            'why_items' => ['nullable', 'string', 'max:3000'],
        ]);

        $whyItems = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['why_items'] ?? '')))
            ->map(fn (string $line) => array_map('trim', array_pad(explode('|', $line, 2), 2, '')))
            ->filter(fn (array $parts) => $parts[0] !== '')
            ->take(8)
            ->map(fn (array $parts) => ['title' => $parts[0], 'body' => $parts[1]])
            ->values()
            ->all();

        $this->settings->set([
            'home.hero_eyebrow' => $validated['hero_eyebrow'],
            'home.intro' => $validated['intro'],
            'home.cta_heading' => $validated['cta_heading'],
            'home.cta_text' => $validated['cta_text'],
            'home.why_items' => $whyItems,
        ]);

        return to_route('admin.content.home.edit')->with('status', 'Homepage content saved.');
    }
}
