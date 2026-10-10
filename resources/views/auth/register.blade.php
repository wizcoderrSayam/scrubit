<x-layouts.auth title="Create account" heading="Create your account" subheading="Track bookings, approve quotes and pay securely.">
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <x-ui.input name="name" label="Full name" autocomplete="name" required autofocus />
        <x-ui.input name="email" label="Email address" type="email" autocomplete="email" required />
        <x-ui.input name="phone" label="Phone number" type="tel" autocomplete="tel" hint="Optional. We'll only use it about your bookings." />
        <x-ui.input name="password" label="Password" type="password" autocomplete="new-password" required />
        <x-ui.input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />

        <x-ui.button class="w-full">Create account</x-ui.button>
    </form>

    <x-slot:footer>
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Log in</a>
    </x-slot:footer>
</x-layouts.auth>
