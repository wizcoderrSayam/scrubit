<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Demo checkout</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
        <p class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">DEMO PAYMENT GATEWAY · no real money is charged</p>
        <h1 class="mt-4 text-2xl font-semibold">Pay {{ \App\Support\Money::format($payment->amount_cents, $payment->currency) }}</h1>
        <p class="mt-1 text-sm text-ink-700">Booking {{ $payment->booking->reference }} · {{ $payment->booking->service_name }}</p>
        <p class="mt-4 text-sm text-ink-700">Choose an outcome to simulate. Each choice sends a signed webhook through the same verification and processing code a real provider would use.</p>

        <form method="POST" action="{{ route('demo-gateway.complete', $payment->idempotency_key) }}" class="mt-6 grid gap-3">
            @csrf
            <button name="outcome" value="succeed" class="rounded-lg bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Simulate successful payment</button>
            <button name="outcome" value="processing" class="rounded-lg border border-line px-4 py-3 font-semibold hover:bg-slate-50">Simulate pending (processing)</button>
            <button name="outcome" value="fail" class="rounded-lg border border-red-300 px-4 py-3 font-semibold text-red-800 hover:bg-red-50">Simulate declined card</button>
            <button name="outcome" value="cancel" class="rounded-lg px-4 py-3 font-semibold text-ink-700 hover:bg-slate-50">Cancel and return</button>
        </form>
    </main>
</body>
</html>
