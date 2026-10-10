<x-layouts.public title="Cleaning services" description="Browse professional residential and commercial cleaning services, then customize and book online.">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold">Our cleaning services</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-700">Choose a service, tell us what you need in a quick chat, and we will take care of the rest.</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-16 px-4 py-14 sm:px-6 lg:px-8">
        @if ($categories === [])
            <div class="rounded-2xl border border-dashed border-brand-200 p-10 text-center">
                <p class="font-semibold">No services are available right now.</p>
                <p class="mt-1 text-ink-700">Please <a href="{{ route('contact') }}" class="text-brand-700 underline">contact us</a> and we will be happy to help.</p>
            </div>
        @else
            @include('services.partials.category-section', ['nodes' => $categories, 'depth' => 0])
        @endif
    </div>
</x-layouts.public>
