<x-layouts.public title="My account" :noindex="true">
    <div class="mx-auto max-w-3xl space-y-8 px-4 py-10 sm:px-6">
        <div>
            <h1 class="text-3xl font-extrabold">My account</h1>
            <p class="mt-1 text-ink-500">Manage your contact details and password.</p>
        </div>

        <x-account.nav />

        <x-ui.card title="Profile" description="We use these details to contact you about your bookings.">
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input name="name" label="Full name" :value="$user->name" bag="updateProfileInformation" autocomplete="name" required />
                <x-ui.input name="email" label="Email address" type="email" :value="$user->email" bag="updateProfileInformation" autocomplete="email" required
                    hint="Changing your email will require verifying the new address." />
                <x-ui.input name="phone" label="Phone number" type="tel" :value="$user->phone" bag="updateProfileInformation" autocomplete="tel" />

                <x-ui.button>Save profile</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Password">
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input name="current_password" label="Current password" type="password" bag="updatePassword" autocomplete="current-password" required />
                <x-ui.input name="password" label="New password" type="password" bag="updatePassword" autocomplete="new-password" required />
                <x-ui.input name="password_confirmation" label="Confirm new password" type="password" bag="updatePassword" autocomplete="new-password" required />

                <x-ui.button>Update password</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.public>
