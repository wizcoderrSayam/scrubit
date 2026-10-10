<x-layouts.public
    :title="$category->seo_title ?: $category->name.' cleaning services'"
    :description="$category->seo_description ?: ($category->description ?: 'Browse '.$category->name.' cleaning services.')"
>
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="text-sm text-ink-500">
                <a href="{{ route('services.index') }}" class="hover:text-brand-700">Services</a> <span aria-hidden="true">/</span> {{ $category->name }}
            </nav>
            <h1 class="mt-3 text-4xl font-bold">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-3 max-w-2xl text-lg text-ink-700">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
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
