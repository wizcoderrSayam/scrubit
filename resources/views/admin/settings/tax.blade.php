<x-layouts.admin title="Tax rates">
    <p class="mb-6 max-w-3xl text-sm text-ink-700">Each active rate is applied once to the booking or quote subtotal and rounded to the nearest cent. Confirm the correct rates for your province with your accountant.</p>

    <div class="max-w-3xl space-y-4">
        @forelse ($rates as $rate)
            <x-ui.card>
                <form method="POST" action="{{ route('admin.settings.tax.update', $rate) }}" class="grid items-end gap-4 sm:grid-cols-5">
                    @csrf
                    @method('PUT')
                    <x-ui.input name="name" label="Name" :value="$rate->name" required class="sm:col-span-2" />
                    <x-ui.input name="rate" label="Rate (%)" :value="rtrim(rtrim(intdiv($rate->rate_bps, 100).'.'.str_pad($rate->rate_bps % 100, 2, '0', STR_PAD_LEFT), '0'), '.')" required />
                    <x-ui.input name="sort_order" label="Order" type="number" min="0" :value="$rate->sort_order" />
                    <x-ui.checkbox name="is_active" label="Active" :checked="$rate->is_active" :id="'active_'.$rate->id" />
                    <div class="flex gap-2 sm:col-span-5">
                        <x-ui.button size="sm">Save</x-ui.button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.settings.tax.destroy', $rate) }}" class="mt-2" x-data x-on:submit="if (! confirm('Delete this tax rate?')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm font-medium text-red-700">Delete</button>
                </form>
            </x-ui.card>
        @empty
            <p class="text-sm text-ink-500">No tax rates. Bookings and quotes will not include tax.</p>
        @endforelse

        <x-ui.card title="Add a tax rate">
            <form method="POST" action="{{ route('admin.settings.tax.store') }}" class="grid items-end gap-4 sm:grid-cols-5">
                @csrf
                <x-ui.input name="name" label="Name" placeholder="GST" required class="sm:col-span-2" />
                <x-ui.input name="rate" label="Rate (%)" placeholder="5" required />
                <x-ui.input name="sort_order" label="Order" type="number" min="0" value="0" />
                <x-ui.checkbox name="is_active" label="Active" :checked="true" id="active_new" />
                <div class="sm:col-span-5"><x-ui.button size="sm">Add rate</x-ui.button></div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.admin>
