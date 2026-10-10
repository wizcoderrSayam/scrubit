<x-layouts.admin title="FAQs">
    <div class="mb-6 flex justify-end"><x-ui.button :href="route('admin.faqs.create')">New FAQ</x-ui.button></div>
    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
        @forelse ($faqs as $faq)
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                <a href="{{ route('admin.faqs.edit', $faq) }}" class="flex-1 font-medium text-admin-accent hover:underline">{{ $faq->question }}</a>
                <span class="text-ink-500">{{ $faq->is_published ? 'Published' : 'Hidden' }} · #{{ $faq->sort_order }}</span>
                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" x-data x-on:submit="if (! confirm('Delete this FAQ?')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button class="font-medium text-red-700">Delete</button>
                </form>
            </div>
        @empty
            <p class="px-4 py-10 text-center text-ink-500">No FAQs yet.</p>
        @endforelse
    </div>
</x-layouts.admin>
