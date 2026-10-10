<x-layouts.public
    :title="$category->seo_title ?: $category->name.' cleaning services'"
    :description="$category->seo_description ?: ($category->description ?: 'Browse '.$category->name.' cleaning services.')"
>
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-ink-500">
                <a href="{{ route('services.index') }}" class="hover:text-brand-700">Services</a>
                @foreach ($ancestors as $ancestor)
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('services.category', $ancestor) }}" class="hover:text-brand-700">{{ $ancestor->name }}</a>
                @endforeach
                <span aria-hidden="true">/</span> {{ $category->name }}
            </nav>
            <h1 class="mt-3 text-4xl font-bold">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-3 max-w-2xl text-lg text-ink-700">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        @if ($children->isNotEmpty())
            <nav aria-label="Sub-categories" class="mb-10">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-500">Sub-categories</h2>
                <ul class="mt-3 flex flex-wrap gap-3">
                    @foreach ($children as $child)
                        <li>
                            <a href="{{ route('services.category', $child) }}" class="inline-flex rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50">{{ $child->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
        @if ($services->isEmpty())
            <p class="rounded-2xl border border-dashed border-brand-200 p-10 text-center text-ink-700">There are no services in this category yet.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-site.service-card :service="$service" :currency="$business['business.currency']" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
