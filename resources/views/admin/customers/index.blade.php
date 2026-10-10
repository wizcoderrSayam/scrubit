<x-layouts.admin title="Customers">
    <form method="GET" class="mb-6 flex gap-2">
        <label for="q" class="sr-only">Search customers</label>
        <input id="q" name="q" value="{{ $term }}" placeholder="Name, email or phone" class="w-full max-w-sm rounded-lg border border-line px-3 py-2 text-sm">
        <x-ui.button variant="secondary">Search</x-ui.button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Bookings</th><th class="px-4 py-3">Verified</th><th class="px-4 py-3">Joined</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-admin-accent hover:underline">{{ $customer->name }}</a><span class="block text-xs text-ink-500">{{ $customer->email }}</span></td>
                        <td class="px-4 py-3">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $customer->bookings_count }}</td>
                        <td class="px-4 py-3">{{ $customer->email_verified_at ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3">{{ $customer->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</x-layouts.admin>
