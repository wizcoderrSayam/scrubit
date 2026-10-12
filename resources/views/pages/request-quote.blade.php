<x-layouts.public title="Request a quote" description="Get a free, personalized cleaning quote from {{ $business['business.name'] }}.">
    <section class="bg-brand-50/60">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">Get your free quote</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-700">Fill out a quick form and receive a personalized cleaning quote tailored to your home or business.</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-3 lg:px-8">
        <x-ui.card title="Tell us about your needs" class="lg:col-span-2">
            <form method="POST" action="{{ route('quote.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                @include('pages.partials.quote-fields')
                <div class="sm:col-span-2">
                    <x-ui.button>Get a quote</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <aside class="rounded-[2rem] bg-brand-900 p-8 text-white">
            <h2 class="text-lg font-semibold text-white">Prefer to book online?</h2>
            <p class="mt-2 text-sm text-brand-100">Pick a service and describe your requirements in our quick booking chat. You approve the price before paying.</p>
            <x-ui.button :href="route('services.index')" variant="secondary" class="mt-5">Start a booking</x-ui.button>
        </aside>
    </div>
</x-layouts.public>
