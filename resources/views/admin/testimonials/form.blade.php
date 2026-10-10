<x-layouts.admin :title="$testimonial->exists ? 'Edit testimonial' : 'New testimonial'">
    <form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" class="max-w-3xl">
        @csrf
        @if ($testimonial->exists) @method('PUT') @endif
        <x-ui.card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="author_name" label="Customer name" :value="$testimonial->author_name" required />
                <x-ui.select name="service_id" label="Related service" placeholder="None" :options="$services->pluck('name', 'id')->all()" :value="$testimonial->service_id" />
                <x-ui.textarea name="body" label="Testimonial" :value="$testimonial->body" rows="5" required class="sm:col-span-2" />
                <x-ui.input name="sort_order" label="Sort order" type="number" min="0" :value="$testimonial->sort_order ?? 0" />
                <x-ui.checkbox name="is_published" label="Published" :checked="$testimonial->is_published" class="self-end" />
            </div>
            <div class="mt-6 flex gap-3">
                <x-ui.button>Save testimonial</x-ui.button>
                <x-ui.button :href="route('admin.testimonials.index')" variant="ghost">Cancel</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.admin>
