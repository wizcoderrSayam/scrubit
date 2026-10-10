<x-layouts.public title="Frequently asked questions" description="Answers to common questions about booking, quotes and payments with {{ $business['business.name'] }}.">
    <section class="border-b border-brand-100">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 sm:py-20">
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">Frequently asked questions</h1>
            <p class="mt-3 text-lg text-ink-700">Can't find what you are looking for? <a href="{{ route('contact') }}" class="font-semibold text-brand-700 underline">Get in touch</a>.</p>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @if ($faqs->isEmpty())
            <p class="text-ink-700">No questions have been added yet.</p>
        @else
            <div class="divide-y divide-brand-100 rounded-2xl border border-brand-100 bg-white">
                @foreach ($faqs as $faq)
                    <details class="group p-5" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">
                            {{ $faq->question }}
                            <svg class="size-5 shrink-0 text-brand-600 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                        </summary>
                        <div class="content-body mt-3 text-ink-700">{{ $faq->answerHtml() }}</div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
