<x-layouts.public :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
        <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $page->title }}</h1>
        <p class="mt-2 text-sm text-ink-500">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
        <div class="content-body mt-8">{{ $page->bodyHtml() }}</div>
    </article>
</x-layouts.public>
