<x-layouts.admin title="Business settings">
    <form method="POST" action="{{ route('admin.settings.business.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card title="Business details" description="Shown in the website header, footer and customer emails.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="business_name" label="Business name" :value="$settings['business.name']" required class="sm:col-span-2" />
                <x-ui.input name="business_tagline" label="Tagline" :value="$settings['business.tagline']" class="sm:col-span-2" />
                <x-ui.input name="business_phone" label="Phone" type="tel" :value="$settings['business.phone']" required />
                <x-ui.input name="business_email" label="Email" type="email" :value="$settings['business.email']" required />
                <x-ui.input name="business_location" label="Location" :value="$settings['business.location']" />

                <div class="space-y-1.5">
                    <label for="business_currency" class="block text-sm font-medium text-ink-900">Currency <span class="text-red-700" aria-hidden="true">*</span></label>
                    <select id="business_currency" name="business_currency" required
                        class="block w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:outline-2 focus:outline-brand-600">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency }}" @selected(old('business_currency', $settings['business.currency']) === $currency)>{{ $currency }}</option>
                        @endforeach
                    </select>
                    @error('business_currency')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Add-on pricing note" description="Shown prominently on the service customization screen. Use {rate} where the hourly rate should appear.">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-ui.input name="addon_note_text" label="Note text" :value="$settings['addon_note.text']" required class="sm:col-span-2" />
                <x-ui.input name="addon_note_rate" label="Hourly rate" :value="$addonRate" prefix="$" inputmode="decimal" required />
            </div>
            <p class="mt-4 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900">
                Customers currently see: <strong>{{ $addonNotePreview }}</strong>
            </p>
        </x-ui.card>

        <x-ui.card title="Bookings, quotes & cancellations">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input name="booking_min_lead_days" label="Minimum notice (days)" type="number" min="0" max="60"
                    :value="$settings['booking.min_lead_days']" required hint="Earliest preferred date customers can choose, counted from today." />
                <x-ui.input name="booking_max_advance_days" label="Book up to (days ahead)" type="number" min="7" max="365"
                    :value="$settings['booking.max_advance_days']" required />
                <x-ui.input name="quote_default_expiry_days" label="Default quote validity (days)" type="number" min="1" max="90"
                    :value="$settings['quote.default_expiry_days']" required />
                <x-ui.input name="cancellation_cutoff_hours" label="Cancellation cut-off (hours before the visit)" type="number" min="0" max="720"
                    :value="$settings['cancellation.cutoff_hours']" required
                    hint="Customers can request cancellation of a paid booking until this many hours before the visit." />
            </div>
        </x-ui.card>

        <x-ui.card title="Social media" description="Leave blank to hide a link.">
            <div class="grid gap-5">
                <x-ui.input name="social_facebook" label="Facebook URL" type="url" :value="$settings['social.facebook']" placeholder="https://" />
                <x-ui.input name="social_instagram" label="Instagram URL" type="url" :value="$settings['social.instagram']" placeholder="https://" />
                <x-ui.input name="social_youtube" label="YouTube URL" type="url" :value="$settings['social.youtube']" placeholder="https://" />
            </div>
        </x-ui.card>

        <x-ui.button size="lg">Save settings</x-ui.button>
    </form>
</x-layouts.admin>
