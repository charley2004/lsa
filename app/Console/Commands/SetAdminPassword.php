<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

// Self-service credential rotation: run this yourself whenever the admin
// password needs changing (e.g. right before go-live) so the value never
// has to be typed into a chat, a ticket, or anywhere else it could linger.
class SetAdminPassword extends Command
{
    protected $signature = 'lsa:set-admin-password {email?}';

    protected $description = 'Set or rotate a Filament admin user\'s password interactively (input is masked, never echoed or logged)';

    public function handle(): int
    {
        $email = $this->argument('email') ?: text(
            label: 'Admin email',
            default: User::first()?->email ?? 'admin@lsa.local',
        );

        $pass1 = password(label: 'New password (min 12 characters)');
        $pass2 = password(label: 'Confirm new password');

        if ($pass1 !== $pass2) {
            $this->error('Passwords did not match — nothing was changed.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $pass1], [
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->name ?: 'LSA Admin';
        $user->password = Hash::make($pass1);
        $user->save();

        $this->info("Password set for {$email}.");

        return self::SUCCESS;
    }
}
