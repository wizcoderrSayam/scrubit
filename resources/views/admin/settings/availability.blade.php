<x-layouts.admin title="Booking availability">
    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.settings.availability.update') }}" class="xl:col-span-2">
            @csrf
            @method('PUT')
            <x-ui.card title="Weekly schedule" description="Customers can request these days and time windows. You confirm the exact arrival time for each booking.">
                <div class="divide-y divide-slate-100">
                    @foreach ($days as $day)
                        <div class="flex flex-wrap items-center gap-4 py-3">
                            <label class="flex w-40 items-center gap-2 text-sm font-medium">
                                <input type="hidden" name="days[{{ $day['weekday'] }}][is_open]" value="0">
                                <input type="checkbox" name="days[{{ $day['weekday'] }}][is_open]" value="1" @checked($day['is_open']) class="rounded border-line text-brand-600">
                                {{ $day['name'] }}
                            </label>
                            @foreach ($windows as $window)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="days[{{ $day['weekday'] }}][windows][]" value="{{ $window->value }}" @checked(in_array($window->value, $day['windows'], true)) class="rounded border-line text-brand-600">
                                    {{ ucfirst($window->value) }}
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <x-ui.button class="mt-5">Save weekly schedule</x-ui.button>
            </x-ui.card>
        </form>

        <div class="space-y-6">
            <x-ui.card title="Closed dates" description="Holidays and other days you are not taking bookings.">
                <ul class="space-y-2 text-sm">
                    @forelse ($exceptions as $exception)
                        <li class="flex items-center justify-between gap-3">
                            <span>{{ $exception->date->format('D, M j, Y') }}{{ $exception->reason ? ' · '.$exception->reason : '' }}</span>
                            <form method="POST" action="{{ route('admin.settings.availability.exceptions.destroy', $exception) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-medium text-red-700">Remove</button>
                            </form>
                        </li>
                    @empty
                        <li class="text-ink-500">No upcoming closed dates.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.settings.availability.exceptions.store') }}" class="mt-5 space-y-3">
                    @csrf
                    <x-ui.input name="date" label="Date" type="date" required />
                    <x-ui.input name="reason" label="Reason (optional)" />
                    <x-ui.button variant="secondary" size="sm">Add closed date</x-ui.button>
                </form>
            </x-ui.card>
            <p class="text-sm text-ink-500">Minimum notice and how far ahead customers can book are set in <a href="{{ route('admin.settings.business.edit') }}" class="text-admin-accent underline">Business settings</a>.</p>
        </div>
    </div>
</x-layouts.admin>
