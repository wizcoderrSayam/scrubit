<x-layouts.public :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">About Us</p>
            <h1 class="mt-2 text-4xl font-bold">{{ $page->title }}</h1>
        </div>
    </section>

    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6">
        <div class="content-body">{{ $page->bodyHtml() }}</div>

        <div class="mt-12 flex flex-wrap gap-3">
            <x-ui.button :href="route('services.index')" size="lg">Explore our services</x-ui.button>
            <x-ui.button :href="route('contact')" variant="secondary" size="lg">Contact us</x-ui.button>
        </div>
    </article>

    @if ($testimonials->isNotEmpty())
        <section class="bg-brand-50/60">
            <div class="mx-auto max-w-5xl px-4 py-14 sm:px-6">
                <h2 class="text-2xl font-semibold">What our clients say</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-2">
                    @foreach ($testimonials as $testimonial)
                        <figure class="rounded-2xl bg-white p-6 shadow-xs">
                            <blockquote class="text-ink-700">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                            <figcaption class="mt-4 font-semibold">{{ $testimonial->author_name }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
