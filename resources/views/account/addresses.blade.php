<x-layouts.public title="Saved addresses" :noindex="true">
    <div class="mx-auto max-w-4xl space-y-8 px-4 py-10 sm:px-6">
        <div>
            <h1 class="text-3xl font-semibold">Saved addresses</h1>
            <p class="mt-1 text-ink-500">Your default address is filled in automatically when you book.</p>
        </div>

        <x-account.nav />

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($addresses as $address)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-semibold">{{ $address->label ?: 'Address' }}</p>
                        @if ($address->is_default)
                            <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-800">Default</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-ink-700">{{ $address->oneLine() }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @unless ($address->is_default)
                            <form method="POST" action="{{ route('account.addresses.update', $address) }}">
                                @csrf
                                @method('PUT')
                                @foreach (['label', 'line1', 'line2', 'city', 'province', 'postal_code', 'notes'] as $field)
                                    <input type="hidden" name="{{ $field }}" value="{{ $address->{$field} }}">
                                @endforeach
                                <input type="hidden" name="is_default" value="1">
                                <x-ui.button variant="secondary" size="sm">Make default</x-ui.button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button variant="ghost" size="sm">Remove</x-ui.button>
                        </form>
                    </div>
                </x-ui.card>
            @empty
                <p class="text-sm text-ink-700 md:col-span-2">You have no saved addresses yet.</p>
            @endforelse
        </div>

        <x-ui.card title="Add an address">
            <form method="POST" action="{{ route('account.addresses.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <x-ui.input name="label" label="Label" placeholder="Home, Office…" />
                <x-ui.input name="line1" label="Street address" autocomplete="address-line1" required />
                <x-ui.input name="line2" label="Unit / suite" autocomplete="address-line2" />
                <x-ui.input name="city" label="City" autocomplete="address-level2" required />
                <x-ui.select name="province" label="Province" :options="$provinces" value="BC" required />
                <x-ui.input name="postal_code" label="Postal code" autocomplete="postal-code" required />
                <x-ui.input name="notes" label="Access notes" class="sm:col-span-2" />
                <x-ui.checkbox name="is_default" label="Make this my default address" class="sm:col-span-2" />
                <div class="sm:col-span-2"><x-ui.button>Save address</x-ui.button></div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.public>
