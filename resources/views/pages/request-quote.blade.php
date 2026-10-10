@php($selectedService = $services->firstWhere('slug', request('service'))?->id)

<x-layouts.public title="Request a quote" description="Get a free, personalized cleaning quote from {{ $business['business.name'] }}.">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold">Get your free quote</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-700">Fill out a quick form and receive a personalized cleaning quote tailored to your home or business.</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-3 lg:px-8">
        <x-ui.card title="Tell us about your needs" class="lg:col-span-2">
            <form method="POST" action="{{ route('quote.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <x-ui.input name="name" label="Name" autocomplete="name" required />
                <x-ui.input name="phone" label="Phone" type="tel" autocomplete="tel" required />
                <x-ui.input name="email" label="Email address" type="email" autocomplete="email" required />
                <x-ui.select name="service_id" label="Select a service" placeholder="Not sure yet"
                    :options="$services->pluck('name', 'id')->all()" :value="$selectedService" />
                <x-ui.textarea name="message" label="Additional information" rows="6" class="sm:col-span-2"
                    hint="Property size, rooms, condition, preferred dates, anything that helps us quote accurately." />
                <div class="sm:col-span-2">
                    <x-ui.button>Get a quote</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <aside class="rounded-2xl bg-brand-800 p-6 text-white">
            <h2 class="text-lg font-semibold text-white">Prefer to book online?</h2>
            <p class="mt-2 text-sm text-brand-100">Pick a service and describe your requirements in our quick booking chat. You approve the price before paying.</p>
            <x-ui.button :href="route('services.index')" variant="secondary" class="mt-5">Start a booking</x-ui.button>
        </aside>
    </div>
</x-layouts.public>
