<x-layouts.admin :title="$category->exists ? 'Edit '.$category->name : 'New category'">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-2xl">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <x-ui.card>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="name" label="Name" :value="$category->name" required />
                <x-ui.input name="slug" label="URL slug" :value="$category->slug" hint="Leave blank to generate from the name." />
                <x-ui.select name="parent_id" label="Parent category" :options="$parentOptions" :value="$category->parent_id" placeholder="None (top level)" class="sm:col-span-2" hint="Leave empty for a top-level category. A category cannot sit under itself." />
                <x-ui.textarea name="description" label="Description" :value="$category->description" rows="3" class="sm:col-span-2" />
                <x-ui.input name="seo_title" label="SEO title" :value="$category->seo_title" />
                <x-ui.input name="sort_order" label="Sort order" type="number" min="0" :value="$category->sort_order ?? 0" />
                <x-ui.textarea name="seo_description" label="Meta description" :value="$category->seo_description" rows="2" class="sm:col-span-2" />
                <x-ui.checkbox name="is_active" label="Visible on the website" :checked="$category->is_active" class="sm:col-span-2" />
            </div>
            <div class="mt-6 flex gap-3">
                <x-ui.button>Save category</x-ui.button>
                <x-ui.button :href="route('admin.categories.index')" variant="ghost">Cancel</x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-layouts.admin>
