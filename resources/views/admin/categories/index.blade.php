<x-layouts.admin title="Service categories">
    <div class="mb-6 flex justify-end"><x-ui.button :href="route('admin.categories.create')">New category</x-ui.button></div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Category</th><th class="px-4 py-3">Services</th><th class="px-4 py-3">Order</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.categories.edit', $category) }}" class="font-medium text-admin-accent hover:underline">{{ $category->name }}</a><span class="block text-xs text-ink-500">/services/category/{{ $category->slug }}</span></td>
                        <td class="px-4 py-3">{{ $category->services_count }}</td>
                        <td class="px-4 py-3">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">{{ $category->is_active ? 'Active' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" x-data x-on:submit="if (! confirm('Delete this category?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-medium text-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">No categories yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
