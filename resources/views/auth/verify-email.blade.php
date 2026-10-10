<x-layouts.auth title="Verify your email" heading="Verify your email address" subheading="We've sent a verification link to your email. Click it to activate your account.">
    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button class="w-full">Resend verification email</x-ui.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button variant="ghost" class="w-full">Log out</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
