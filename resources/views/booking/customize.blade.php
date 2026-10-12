@php
    $stepLabels = [
        'needs' => 'Your cleaning needs',
        'schedule' => 'Date, time'.($service->allows_recurring ? ' & frequency' : ''),
        'details' => 'Address & contact details',
        'extras' => 'Add-ons & extra requests',
    ];
    $closedDayNames = collect($availability['closedWeekdays'])->map(fn ($day) => \App\Models\AvailabilityRule::WEEKDAYS[$day].'s')->implode(', ');
@endphp

<x-layouts.booking :title="'Customize '.$service->name" :step="2">
    <div x-data="bookingChat(@js($chat))" class="grid gap-6 lg:grid-cols-3">
        <section class="flex min-h-[32rem] flex-col overflow-hidden rounded-[22px] border border-brand-100 bg-white shadow-xs lg:col-span-2" aria-labelledby="chat-heading">
            <header class="border-b border-brand-100 px-5 py-4">
                <h1 id="chat-heading" class="text-xl font-semibold">{{ $service->name }}</h1>
                <p class="mt-1 text-sm text-ink-700">{{ $service->short_description }}</p>
            </header>

            <div class="border-b border-bubble-400/40 bg-bubble-400/10 px-5 py-3" role="note">
                <p class="text-base font-semibold text-ink-900">{{ $addonNote }}</p>
                <p class="text-xs text-ink-700">Add-ons are only charged when you select them below or approve them in your quote.</p>
            </div>

            <div x-ref="log" class="flex-1 space-y-4 overflow-y-auto bg-brand-50/50 px-4 py-5 sm:px-5" role="log" aria-live="polite" aria-label="Conversation" style="max-height: 32rem">
                <template x-for="message in messages" :key="message.id">
                    <div class="flex" :class="message.sender === 'customer' ? 'justify-end' : 'justify-start'">
                        <div class="max-w-[85%] rounded-[22px] px-4 py-2.5 text-sm leading-relaxed shadow-xs"
                            :class="message.sender === 'customer'
                                ? 'rounded-br-md bg-brand-600 text-white'
                                : (message.sender === 'system' ? 'bg-amber-50 text-amber-900' : 'rounded-bl-md bg-white text-ink-900')">
                            <p class="whitespace-pre-line" x-text="message.body"></p>
                            <p class="mt-1 text-[11px]" :class="message.sender === 'customer' ? 'text-brand-100' : 'text-ink-500'" x-text="message.time"></p>
                        </div>
                    </div>
                </template>
                <div x-show="sending" x-cloak class="flex justify-start" aria-hidden="true">
                    <div class="rounded-[22px] bg-white px-4 py-3 text-sm text-ink-500 shadow-xs">Typing…</div>
                </div>
            </div>

            <div class="border-t border-brand-100 bg-white px-4 py-4 sm:px-5">
                <p x-show="notice" x-cloak x-text="notice" class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800" role="alert"></p>

                {{-- Structured steps --}}
                <div x-show="activeWidget === 'schedule'" x-cloak class="mb-4 space-y-3">
                    <p class="text-sm font-semibold">Preferred date and time window</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="preferred_date" class="block text-sm font-medium">Date</label>
                            <input id="preferred_date" type="date" x-model="form.preferred_date" min="{{ $availability['min'] }}" max="{{ $availability['max'] }}"
                                class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
                            <p class="mt-1 text-sm text-red-700" x-text="error('preferred_date')"></p>
                        </div>
                        <div>
                            <label for="time_window" class="block text-sm font-medium">Time window</label>
                            <select id="time_window" x-model="form.time_window" class="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                @foreach ($windows as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-red-700" x-text="error('time_window')"></p>
                        </div>
                    </div>
                    @if ($closedDayNames)
                        <p class="text-xs text-ink-500">We are closed on {{ $closedDayNames }}.</p>
                    @endif

                    @if ($service->allows_recurring)
                        <p class="pt-1 text-sm font-semibold">How often should we visit?</p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($frequencies as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                    <input type="radio" value="{{ $value }}" x-model="form.frequency" class="text-brand-600"> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-sm text-red-700" x-text="error('frequency')"></p>
                    @endif

                    <x-ui.button type="button" x-bind:disabled="sending"
                        x-on:click="submitStep('schedule', { preferred_date: form.preferred_date, time_window: form.time_window, frequency: form.frequency })">Save &amp; continue</x-ui.button>
                </div>

                <div x-show="activeWidget === 'details'" x-cloak class="mb-4 space-y-4">
                    <div>
                        <p class="text-sm font-semibold">Service address</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            @foreach (['line1' => 'Street address', 'line2' => 'Unit / suite (optional)', 'city' => 'City', 'postal_code' => 'Postal code'] as $field => $label)
                                <div>
                                    <label for="addr_{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                                    <input id="addr_{{ $field }}" type="text" x-model="form.{{ $field }}" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm"
                                        autocomplete="{{ ['line1' => 'address-line1', 'line2' => 'address-line2', 'city' => 'address-level2', 'postal_code' => 'postal-code'][$field] }}">
                                    <p class="mt-1 text-sm text-red-700" x-text="error('{{ $field }}')"></p>
                                </div>
                            @endforeach
                            <div>
                                <label for="addr_province" class="block text-sm font-medium">Province</label>
                                <select id="addr_province" x-model="form.province" class="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                    @foreach ($provinces as $code => $name)
                                        <option value="{{ $code }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-red-700" x-text="error('province')"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="addr_notes" class="block text-sm font-medium">Access notes (optional)</label>
                                <input id="addr_notes" type="text" x-model="form.notes" placeholder="Buzzer code, parking, pets…" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
                            </div>
                        </div>
                        @if ($isLoggedIn)
                            <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.save_address" class="rounded border-line text-brand-600"> Save this address to my account</label>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold">Your contact details</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-3">
                            @foreach (['contact_name' => ['Full name', 'text', 'name'], 'contact_email' => ['Email', 'email', 'email'], 'contact_phone' => ['Phone', 'tel', 'tel']] as $field => [$label, $type, $autocomplete])
                                <div>
                                    <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                                    <input id="{{ $field }}" type="{{ $type }}" x-model="form.{{ $field }}" autocomplete="{{ $autocomplete }}" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm">
                                    <p class="mt-1 text-sm text-red-700" x-text="error('{{ $field }}')"></p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <x-ui.button type="button" x-bind:disabled="sending"
                        x-on:click="submitStep('details', { line1: form.line1, line2: form.line2, city: form.city, province: form.province, postal_code: form.postal_code, notes: form.notes, save_address: form.save_address, contact_name: form.contact_name, contact_email: form.contact_email, contact_phone: form.contact_phone })">Save &amp; continue</x-ui.button>
                </div>

                <div x-show="activeWidget === 'extras'" x-cloak class="mb-4 space-y-3">
                    <p class="text-sm font-semibold">Add-ons and extra requests</p>
                    @if (count($addons))
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($addons as $addon)
                                <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-line px-3 py-2 text-sm has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                    <input type="checkbox" class="mt-0.5 rounded border-line text-brand-600" :checked="form.addons.includes({{ $addon['id'] }})" x-on:change="toggleAddon({{ $addon['id'] }})">
                                    <span><span class="font-medium">{{ $addon['name'] }}</span> <span class="text-ink-500">· {{ $addon['price'] }}</span>
                                        @if ($addon['description'])<span class="block text-xs text-ink-500">{{ $addon['description'] }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-ink-700">There are no pre-priced add-ons for this service.</p>
                    @endif
                    <div>
                        <label for="extra_request" class="block text-sm font-medium">Anything else? (reviewed and quoted by our team)</label>
                        <textarea id="extra_request" rows="2" x-model="form.extra_request" class="mt-1 block w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="e.g. inside the oven, interior windows, laundry"></textarea>
                        <p class="mt-1 text-sm text-red-700" x-text="error('addons') || error('extra_request')"></p>
                    </div>
                    <x-ui.button type="button" x-bind:disabled="sending" x-on:click="submitStep('extras', { addons: form.addons, extra_request: form.extra_request })">Save &amp; finish</x-ui.button>
                </div>

                {{-- Free text --}}
                <div x-show="currentSuggestions.length" x-cloak class="mb-3 flex flex-wrap gap-2" aria-label="Suggested messages">
                    <template x-for="suggestion in currentSuggestions" :key="suggestion">
                        <button type="button" class="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-left text-xs text-brand-900 hover:border-brand-400" x-text="suggestion" x-on:click="useSuggestion(suggestion)"></button>
                    </template>
                </div>
                <form x-on:submit.prevent="send()" class="flex items-end gap-2">
                    <label for="chat-input" class="sr-only">Your message</label>
                    <textarea id="chat-input" x-ref="input" x-model="input" rows="2" maxlength="2000" :placeholder="placeholder"
                        x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); send(); }"
                        class="block w-full resize-none rounded-xl border border-line px-3 py-2.5 text-sm focus:outline-2 focus:outline-brand-600"></textarea>
                    <x-ui.button x-bind:disabled="sending || ! input.trim()" class="shrink-0">Send</x-ui.button>
                </form>
                <p class="mt-1 text-sm text-red-700" x-text="error('message')"></p>
            </div>
        </section>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-[22px] border border-brand-100 bg-white p-5">
                <h2 class="font-semibold">Your booking details</h2>
                <ol class="mt-4 space-y-2 text-sm">
                    @foreach ($stepLabels as $key => $label)
                        <template x-if="state.steps.includes('{{ $key }}')">
                            <li class="flex items-center gap-2">
                                <span class="flex size-5 items-center justify-center rounded-full text-[10px] font-bold"
                                    :class="state.steps.indexOf('{{ $key }}') < (state.step === 'done' ? 99 : state.steps.indexOf(state.step)) ? 'bg-brand-600 text-white' : (state.step === '{{ $key }}' ? 'border-2 border-brand-600 text-brand-700' : 'border border-line text-ink-500')">
                                    <span x-show="state.steps.indexOf('{{ $key }}') < (state.step === 'done' ? 99 : state.steps.indexOf(state.step))">✓</span>
                                </span>
                                <span :class="state.step === '{{ $key }}' ? 'font-semibold text-brand-800' : 'text-ink-700'">{{ $label }}</span>
                            </li>
                        </template>
                    @endforeach
                </ol>

                <a :href="state.complete ? urls.review : null" :aria-disabled="(! state.complete).toString()"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors"
                    :class="state.complete ? 'bg-brand-600 text-white hover:bg-brand-800' : 'pointer-events-none bg-slate-200 text-ink-500'">
                    Continue to Review
                </a>
                <p x-show="! state.complete" class="mt-2 text-xs text-ink-500">Answer the remaining questions to continue.</p>
            </div>

            <div class="rounded-[22px] border border-brand-100 bg-white p-5 text-sm text-ink-700">
                <p class="font-semibold text-ink-900">How pricing works</p>
                <p class="mt-1">{{ $service->requiresQuote() ? 'This service is priced by quote. We review your details and send a price for approval before you pay anything.' : 'This service has a set price. You will see the full breakdown before paying.' }}</p>
            </div>
        </aside>
    </div>
</x-layouts.booking>
