<x-layouts.admin :title="$addon->exists ? 'Edit '.$addon->name : 'New add-on'">
    <form method="POST" action="{{ $addon->exists ? route('admin.addons.update', $addon) : route('admin.addons.store') }}" class="max-w-2xl"
        x-data="{ method: @js(old('pricing_method', $addon->pricing_method?->value ?? 'hourly')) }">
        @csrf
        @if ($addon->exists) @method('PUT') @endif
        <x-ui.card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="name" label="Name" :value="$addon->name" required />
                <x-ui.input name="slug" label="Slug" :value="$addon->slug" hint="Leave blank to generate from the name." />
                <x-ui.textarea name="description" label="Description" :value="$addon->description" rows="2" class="sm:col-span-2" />
                <div class="space-y-1.5">
                    <label for="pricing_method" class="block text-sm font-medium">Pricing</label>
                    <select id="pricing_method" name="pricing_method" x-model="method" class="block w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                        @foreach ($pricingMethods as $pricingMethod)
                            <option value="{{ $pricingMethod->value }}">{{ $pricingMethod->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="method !== 'quote'">
                    <x-ui.input name="price" label="Price" prefix="$" inputmode="decimal" :value="$addon->price_cents !== null ? \App\Support\Money::toDecimal($addon->price_cents) : null" />
                </div>
                <x-ui.input name="sort_order" label="Sort order" type="number" min="0" :value="$addon->sort_order ?? 0" />
                <x-ui.checkbox name="is_active" label="Active" :checked="$addon->is_active" class="sm:col-span-2" />
            </div>
            <div class="mt-6 flex gap-3">
                <x-ui.button>Save add-on</x-ui.button>
                <x-ui.button :href="route('admin.addons.index')" variant="ghost">Cancel</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.admin>
