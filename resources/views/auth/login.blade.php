<x-layouts.auth title="Log in" heading="Log in to your account" subheading="View your bookings, quotes and payment status.">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-ui.input name="email" label="Email address" type="email" autocomplete="email" required autofocus />
        <x-ui.input name="password" label="Password" type="password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-4 text-sm">
            <label class="inline-flex items-center gap-2 text-ink-700">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-line text-brand-600" @checked(old('remember'))>
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:text-brand-900">Forgot password?</a>
        </div>

        <x-ui.button class="w-full">Log in</x-ui.button>
    </form>

    <x-slot:footer>
        New here?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-900">Create an account</a>
    </x-slot:footer>
</x-layouts.auth>
