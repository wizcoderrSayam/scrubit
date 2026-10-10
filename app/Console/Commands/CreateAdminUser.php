<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

#[Signature('admin:create
    {--name= : The administrator\'s display name}
    {--email= : The administrator\'s email address}
    {--password= : The password (omit to be prompted securely; avoid leaving it in shell history)}
    {--force : Update an existing user with this email without asking}')]
#[Description('Create an administrator account, or promote and reset an existing user')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name', config('scrubit.admin.name'));
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $password = $this->option('password');
        $passwordConfirmation = $password;

        if (blank($password)) {
            $password = $this->secret('Password');
            $passwordConfirmation = $this->secret('Confirm password');
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        if ($admin->exists && ! $this->option('force')
            && ! $this->confirm("A user with {$email} already exists. Make them an admin, set their name to {$name} and reset their password?")) {
            $this->warn('No changes made.');

            return self::FAILURE;
        }

        $wasExisting = $admin->exists;

        $admin->forceFill([
            'name' => $name,
            'password' => $password,
            'role' => UserRole::Admin,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->info($wasExisting ? "Administrator {$email} updated." : "Administrator {$email} created.");

        return self::SUCCESS;
    }
}
