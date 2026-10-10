<x-layouts.admin title="Add-on services">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-700">Add-ons are only charged when a customer selects them. Quote-type add-ons are flagged for review.</p>
        <x-ui.button :href="route('admin.addons.create')">New add-on</x-ui.button>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Add-on</th><th class="px-4 py-3">Pricing</th><th class="px-4 py-3">Services</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($addons as $addon)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.addons.edit', $addon) }}" class="font-medium text-admin-accent hover:underline">{{ $addon->name }}</a></td>
                        <td class="px-4 py-3">{{ $addon->pricing_method->label() }} · {{ $addon->priceLabel() }}</td>
                        <td class="px-4 py-3">{{ $addon->services_count }}</td>
                        <td class="px-4 py-3">{{ $addon->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.addons.destroy', $addon) }}" x-data x-on:submit="if (! confirm('Delete this add-on?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-medium text-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">No add-ons yet. Customers can still describe extra tasks in the chat; those are flagged for a quote.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
