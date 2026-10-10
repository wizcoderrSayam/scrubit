<x-layouts.auth title="Confirm password" heading="Confirm your password" subheading="This is a secure area. Please confirm your password to continue.">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <x-ui.input name="password" label="Password" type="password" autocomplete="current-password" required autofocus />

        <x-ui.button class="w-full">Confirm</x-ui.button>
    </form>
</x-layouts.auth>
