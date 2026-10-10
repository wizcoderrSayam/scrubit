<x-layouts.auth title="Choose a new password" heading="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.input name="email" label="Email address" type="email" autocomplete="email" :value="$request->email" required />
        <x-ui.input name="password" label="New password" type="password" autocomplete="new-password" required autofocus />
        <x-ui.input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />

        <x-ui.button class="w-full">Reset password</x-ui.button>
    </form>
</x-layouts.auth>
