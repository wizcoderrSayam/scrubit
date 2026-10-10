<x-layouts.admin :title="'Edit '.$page->title">
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="max-w-4xl">
        @csrf
        @method('PUT')
        <x-ui.card>
            <div class="grid gap-5">
                <x-ui.input name="title" label="Title" :value="$page->title" required />
                <x-ui.textarea name="body" label="Content (Markdown)" :value="$page->body" rows="20" required
                    hint="Use ## for headings, - for bullet lists and **bold**. HTML is not allowed." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-ui.input name="seo_title" label="SEO title" :value="$page->seo_title" />
                    <x-ui.input name="seo_description" label="Meta description" :value="$page->seo_description" />
                </div>
                <x-ui.checkbox name="is_published" label="Published" :checked="$page->is_published" />
            </div>
            <div class="mt-6 flex gap-3">
                <x-ui.button>Save page</x-ui.button>
                <x-ui.button :href="$page->slug === 'about' ? route('about') : route('pages.show', $page)" variant="ghost" target="_blank">View page</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.admin>
