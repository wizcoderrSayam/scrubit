<x-layouts.admin title="Pages">
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-sm font-medium text-ink-500">
                <tr><th class="px-4 py-3">Page</th><th class="px-4 py-3">URL</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Updated</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($pages as $page)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.pages.edit', $page) }}" class="font-medium text-admin-accent hover:underline">{{ $page->title }}</a></td>
                        <td class="px-4 py-3 text-ink-500">{{ $page->slug === 'about' ? '/about' : '/pages/'.$page->slug }}</td>
                        <td class="px-4 py-3">{{ $page->is_published ? 'Published' : 'Hidden' }}</td>
                        <td class="px-4 py-3">{{ $page->updated_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
