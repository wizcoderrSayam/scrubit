@php
    $tabs = [
        ['label' => 'My bookings', 'route' => 'account.bookings'],
        ['label' => 'Saved addresses', 'route' => 'account.addresses.index'],
        ['label' => 'Profile', 'route' => 'account'],
    ];
@endphp

<nav aria-label="Account" class="flex flex-wrap gap-2 border-b border-brand-100 pb-3">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
            @class([
                'rounded-full px-4 py-1.5 text-sm font-medium',
                'bg-brand-600 text-white' => request()->routeIs($tab['route']),
                'text-ink-700 hover:bg-brand-50' => ! request()->routeIs($tab['route']),
            ])
            @if (request()->routeIs($tab['route'])) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
