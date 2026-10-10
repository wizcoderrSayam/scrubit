@props(['title' => null, 'actionLabel' => null, 'actionHref' => null])

@php($headingId = 'table-'.\Illuminate\Support\Str::random(8))

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white']) }}>
    @if ($title || $actionHref)
        <header class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            @if ($title)
                <h2 id="{{ $headingId }}" class="text-base font-semibold text-ink-900">{{ $title }}</h2>
            @endif
            @if ($actionHref)
                <a href="{{ $actionHref }}" class="text-sm font-medium text-admin-accent hover:underline">{{ $actionLabel ?? 'View all' }}</a>
            @endif
        </header>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm" @if ($title) aria-labelledby="{{ $headingId }}" @endif>
            @isset($head)
                <thead class="bg-slate-50 text-left text-ink-500">
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="border-t border-slate-200 px-5 py-3">{{ $footer }}</div>
    @endisset
</section>
