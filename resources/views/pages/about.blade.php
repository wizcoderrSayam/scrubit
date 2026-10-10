<x-layouts.public :title="$page->seo_title ?: $page->title" :description="$page->seo_description">
    <section class="border-b border-brand-100">
        <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 sm:py-20">
            <p class="text-sm font-medium text-brand-700">About Us</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ $page->title }}</h1>
        </div>
    </section>

    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="content-body">{{ $page->bodyHtml() }}</div>

        <div class="mt-12 flex flex-wrap gap-3">
            <x-ui.button :href="route('services.index')" size="lg">Explore our services</x-ui.button>
            <x-ui.button :href="route('contact')" variant="secondary" size="lg">Contact us</x-ui.button>
        </div>
    </article>

    @if ($testimonials->isNotEmpty())
        <section class="border-t border-brand-100">
            <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 sm:py-20">
                <h2 class="text-3xl font-semibold tracking-tight">What our clients say</h2>
                <div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2">
                    @foreach ($testimonials as $testimonial)
                        <figure class="border-t border-brand-200 pt-6">
                            <blockquote class="leading-relaxed text-ink-700">&ldquo;{{ $testimonial->body }}&rdquo;</blockquote>
                            <figcaption class="mt-4 text-sm font-semibold">{{ $testimonial->author_name }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
