<x-layouts.admin title="Payment settings">
    <div class="max-w-3xl space-y-6">
        @if ($isDemo)
            <x-ui.alert type="info">The <strong>demo payment gateway</strong> is active. Customers see a simulated checkout and no real money is collected. Configure a live provider before launch.</x-ui.alert>
        @endif

        <x-ui.card title="Configuration" description="Payment credentials are stored in the server environment (.env) and never in the database.">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-500">Driver (PAYMENT_DRIVER)</dt><dd class="font-semibold">{{ $driver }}</dd></div>
                <div><dt class="text-ink-500">Currency</dt><dd class="font-semibold">{{ $currency }} <a href="{{ route('admin.settings.business.edit') }}" class="ml-1 font-normal text-admin-accent hover:underline">change</a></dd></div>
                <div><dt class="text-ink-500">Checkout session lifetime</dt><dd class="font-semibold">{{ $checkoutTtl }} minutes</dd></div>
                <div class="sm:col-span-2"><dt class="text-ink-500">Webhook URL</dt><dd class="break-all font-mono text-xs">{{ $webhookUrl }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="How payments are confirmed">
            <ul class="list-disc space-y-1 pl-5 text-sm text-ink-700">
                <li>Amounts are calculated on the server from the booking or approved quote.</li>
                <li>Bookings are confirmed only by verified, signed webhook events, never by the customer returning to the website.</li>
                <li>Duplicate webhook deliveries are recorded once and ignored.</li>
                <li>Payments received for cancelled bookings or replaced quotes are flagged under <a href="{{ route('admin.payments.index', ['attention' => 1]) }}" class="text-admin-accent underline">Payments needing attention</a>.</li>
            </ul>
        </x-ui.card>
    </div>
</x-layouts.admin>
