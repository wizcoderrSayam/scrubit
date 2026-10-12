@foreach ($nodes as $node)
    @php($category = $node['category'])
    <section @class(['mt-10 border-l border-brand-200 pl-4 sm:pl-6' => $depth > 0]) aria-labelledby="category-{{ $category->id }}">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                @if ($depth === 0)
                    <h2 id="category-{{ $category->id }}" class="text-2xl font-extrabold">{{ $category->name }}</h2>
                @else
                    <h3 id="category-{{ $category->id }}" class="text-xl font-bold">{{ $category->name }}</h3>
                @endif
                @if ($category->description)
                    <p class="mt-1 text-ink-700">{{ $category->description }}</p>
                @endif
            </div>
            <a href="{{ route('services.category', $category) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View {{ strtolower($category->name) }} services</a>
        </div>
        @if ($node['services']->isNotEmpty())
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($node['services'] as $service)
                    <x-site.service-card :service="$service" :currency="$business['business.currency']" />
                @endforeach
            </div>
        @endif
        @if ($node['children'] !== [])
            <div class="mt-8">
                @include('services.partials.category-section', ['nodes' => $node['children'], 'depth' => $depth + 1])
            </div>
        @endif
    </section>
@endforeach
