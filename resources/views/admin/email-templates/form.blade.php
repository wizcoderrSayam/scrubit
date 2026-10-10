<x-layouts.admin :title="$template->name">
    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.email-templates.update', $template) }}" class="xl:col-span-2">
            @csrf
            @method('PUT')
            <x-ui.card>
                <div class="grid gap-5">
                    <x-ui.input name="subject" label="Subject" :value="$template->subject" required />
                    <x-ui.textarea name="body" label="Body (Markdown)" :value="$template->body" rows="14" required />
                </div>
                <div class="mt-6 flex gap-3"><x-ui.button>Save template</x-ui.button></div>
            </x-ui.card>
        </form>
        <div class="space-y-6">
            <x-ui.card title="Placeholders" description="These are replaced with booking details when the email is sent. Values are escaped automatically.">
                <ul class="space-y-1 font-mono text-xs">
                    @foreach ($placeholders as $placeholder)
                        <li>{{ str_repeat('{', 2).$placeholder.str_repeat('}', 2) }}</li>
                    @endforeach
                </ul>
            </x-ui.card>
            <x-ui.card title="Restore default">
                <form method="POST" action="{{ route('admin.email-templates.reset', $template) }}" x-data x-on:submit="if (! confirm('Replace this template with the default wording?')) $event.preventDefault()">
                    @csrf
                    <x-ui.button variant="secondary">Restore default wording</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-layouts.admin>
