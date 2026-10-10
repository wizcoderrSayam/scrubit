<x-layouts.admin title="Testimonials">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-700">Only publish genuine customer feedback.</p>
        <x-ui.button :href="route('admin.testimonials.create')">New testimonial</x-ui.button>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($testimonials as $testimonial)
            <x-ui.card>
                <p class="text-sm text-ink-700">&ldquo;{{ $testimonial->body }}&rdquo;</p>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                    <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="font-medium text-admin-accent hover:underline">{{ $testimonial->author_name }}</a>
                    <span class="text-ink-500">{{ $testimonial->is_published ? 'Published' : 'Hidden' }}{{ $testimonial->service ? ' · '.$testimonial->service->name : '' }}</span>
                    <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" class="ml-auto" x-data x-on:submit="if (! confirm('Delete this testimonial?')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button class="font-medium text-red-700">Delete</button>
                    </form>
                </div>
            </x-ui.card>
        @empty
            <p class="text-ink-500">No testimonials yet.</p>
        @endforelse
    </div>
</x-layouts.admin>
