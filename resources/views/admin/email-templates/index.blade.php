<x-layouts.admin title="Email templates">
    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
        @foreach ($templates as $template)
            <a href="{{ route('admin.email-templates.edit', $template) }}" class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm hover:bg-slate-50">
                <span class="font-medium text-admin-accent hover:underline">{{ $template->name }}</span>
                <span class="flex-1 truncate text-ink-500">{{ $template->subject }}</span>
            </a>
        @endforeach
    </div>
</x-layouts.admin>
