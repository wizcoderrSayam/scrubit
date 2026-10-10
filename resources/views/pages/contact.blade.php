@php($phoneHref = 'tel:'.preg_replace('/[^0-9+]/', '', $business['business.phone']))

<x-layouts.public title="Contact us" description="Get in touch with {{ $business['business.name'] }} by phone, email or our contact form.">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold">Contact us</h1>
            <p class="mt-3 max-w-2xl text-lg text-ink-700">Questions about a service or an existing booking? We are happy to help.</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-3 lg:px-8">
        <aside class="space-y-6">
            <div class="rounded-2xl border border-brand-100 p-6">
                <h2 class="font-semibold">Call us</h2>
                <a href="{{ $phoneHref }}" class="mt-1 block text-lg font-semibold text-brand-800 hover:text-brand-600">{{ $business['business.phone'] }}</a>
            </div>
            <div class="rounded-2xl border border-brand-100 p-6">
                <h2 class="font-semibold">Email</h2>
                <a href="mailto:{{ $business['business.email'] }}" class="mt-1 block text-lg font-semibold text-brand-800 hover:text-brand-600">{{ $business['business.email'] }}</a>
            </div>
            @if ($business['business.location'])
                <div class="rounded-2xl border border-brand-100 p-6">
                    <h2 class="font-semibold">Location</h2>
                    <p class="mt-1 text-ink-700">{{ $business['business.location'] }}</p>
                </div>
            @endif
        </aside>

        <x-ui.card title="Send us a message" class="lg:col-span-2">
            <form method="POST" action="{{ route('contact.store') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <x-ui.input name="name" label="Your name" autocomplete="name" required />
                <x-ui.input name="email" label="Email" type="email" autocomplete="email" required />
                <x-ui.input name="phone" label="Phone" type="tel" autocomplete="tel" />
                <x-ui.input name="subject" label="Subject" />
                <x-ui.textarea name="message" label="Message" rows="6" required class="sm:col-span-2" />
                <div class="sm:col-span-2">
                    <x-ui.button>Send message</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.public>
