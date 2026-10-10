<x-layouts.auth title="Forgot password" heading="Reset your password" subheading="Enter your email and we'll send you a link to choose a new password.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-ui.input name="email" label="Email address" type="email" autocomplete="email" required autofocus />

        <x-ui.button class="w-full">Email reset link</x-ui.button>
    </form>

    <x-slot:footer>
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Back to log in</a>
    </x-slot:footer>
</x-layouts.auth>
