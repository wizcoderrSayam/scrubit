<x-layouts.admin :title="$faq->exists ? 'Edit FAQ' : 'New FAQ'">
    <form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="max-w-3xl">
        @csrf
        @if ($faq->exists) @method('PUT') @endif
        <x-ui.card>
            <div class="grid gap-5">
                <x-ui.input name="question" label="Question" :value="$faq->question" required />
                <x-ui.textarea name="answer" label="Answer (Markdown)" :value="$faq->answer" rows="6" required />
                <x-ui.input name="sort_order" label="Sort order" type="number" min="0" :value="$faq->sort_order ?? 0" />
                <x-ui.checkbox name="is_published" label="Published" :checked="$faq->is_published" />
            </div>
            <div class="mt-6 flex gap-3">
                <x-ui.button>Save FAQ</x-ui.button>
                <x-ui.button :href="route('admin.faqs.index')" variant="ghost">Cancel</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.admin>
