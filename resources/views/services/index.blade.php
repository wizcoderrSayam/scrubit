<x-layouts.public title="Cleaning services" description="Browse professional residential and commercial cleaning services, then customize and book online.">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold">Our cleaning services</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-700">Choose a service, tell us what you need in a quick chat, and we will take care of the rest.</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-16 px-4 py-14 sm:px-6 lg:px-8">
        @forelse ($categories as $category)
            <section aria-labelledby="category-{{ $category->id }}">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="category-{{ $category->id }}" class="text-2xl font-semibold">{{ $category->name }}</h2>
                        @if ($category->description)
                            <p class="mt-1 text-ink-700">{{ $category->description }}</p>
                        @endif
                    </div>
                    <a href="{{ route('services.category', $category) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View {{ strtolower($category->name) }} services</a>
                </div>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->services as $service)
                        <x-site.service-card :service="$service" :currency="$business['business.currency']" />
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-brand-200 p-10 text-center">
                <p class="font-semibold">No services are available right now.</p>
                <p class="mt-1 text-ink-700">Please <a href="{{ route('contact') }}" class="text-brand-700 underline">contact us</a> and we will be happy to help.</p>
            </div>
        @endforelse
    </div>
</x-layouts.public>
