<x-layouts.admin title="Enquiries">
    <div class="space-y-4">
        @forelse ($enquiries as $enquiry)
            <x-ui.card :class="$enquiry->handled_at ? 'opacity-60' : ''">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $enquiry->name }} <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium">{{ $enquiry->type->label() }}</span></p>
                        <p class="text-sm text-ink-500"><a href="mailto:{{ $enquiry->email }}" class="text-admin-accent hover:underline">{{ $enquiry->email }}</a>{{ $enquiry->phone ? ' · '.$enquiry->phone : '' }}{{ $enquiry->service ? ' · '.$enquiry->service->name : '' }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-ink-500">{{ $enquiry->created_at->format('M j, Y g:i a') }}</span>
                        <form method="POST" action="{{ route('admin.enquiries.handle', $enquiry) }}">
                            @csrf
                            <x-ui.button variant="secondary" size="sm">{{ $enquiry->handled_at ? 'Reopen' : 'Mark handled' }}</x-ui.button>
                        </form>
                    </div>
                </div>
                @if ($enquiry->subject)
                    <p class="mt-3 text-sm font-medium">{{ $enquiry->subject }}</p>
                @endif
                @if ($enquiry->message)
                    <p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $enquiry->message }}</p>
                @endif
            </x-ui.card>
        @empty
            <p class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-ink-500">No enquiries yet.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $enquiries->links() }}</div>
</x-layouts.admin>
