<x-layouts.admin title="Services">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2">
            <label for="q" class="sr-only">Search services</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="Search services" class="rounded-lg border border-line px-3 py-2 text-sm">
            <x-ui.button variant="secondary">Search</x-ui.button>
        </form>
        <x-ui.button :href="route('admin.services.create')">New service</x-ui.button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Service</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Pricing</th><th class="px-4 py-3">Bookings</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($services as $service)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.services.edit', $service) }}" class="font-medium text-admin-accent hover:underline">{{ $service->name }}</a><span class="block text-xs text-ink-500">/services/{{ $service->slug }}</span></td>
                        <td class="px-4 py-3">{{ $service->category?->name }}</td>
                        <td class="px-4 py-3">{{ $service->pricing_method->label() }}<span class="block text-xs text-ink-500">{{ $service->priceLabel() }}</span></td>
                        <td class="px-4 py-3">{{ $service->bookings_count }}</td>
                        <td class="px-4 py-3">
                            <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $service->is_active, 'bg-slate-100 text-slate-700' => ! $service->is_active])>{{ $service->is_active ? 'Published' : 'Unpublished' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.services.toggle', $service) }}" class="inline">
                                @csrf
                                <button class="text-sm font-medium text-admin-accent hover:underline">{{ $service->is_active ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">No services yet. <a href="{{ route('admin.services.create') }}" class="text-admin-accent underline">Create the first one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $services->links() }}</div>
</x-layouts.admin>
