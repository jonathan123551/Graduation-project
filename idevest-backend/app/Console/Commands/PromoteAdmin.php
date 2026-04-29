<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Force-promote an email to the admin role.
 *
 * Provides a no-op-safe, bulletproof way to ensure the admin account has
 * `role='admin'` on every deploy. Unlike AdminSeeder (which relies on
 * env() and Eloquent model mass assignment — both have silently failed
 * in Railway deploys), this uses raw DB::table(...)->update(...) and
 * logs every step visibly so you can debug the deploy logs if anything
 * goes wrong.
 *
 * Usage:
 *   php artisan admin:promote                      # uses ADMIN_EMAIL / ADMIN_PASSWORD env
 *   php artisan admin:promote foo@bar.com secret   # explicit
 */
class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote
        {email? : Email of the user to promote (defaults to env ADMIN_EMAIL)}
        {password? : Optional password to (re)set (defaults to env ADMIN_PASSWORD)}';

    protected $description = 'Force-promote a user to admin role via raw DB update (bulletproof seeder alternative).';

    public function handle(): int
    {
        $email    = $this->argument('email')    ?? env('ADMIN_EMAIL', 'admin@idevest.com');
        $password = $this->argument('password') ?? env('ADMIN_PASSWORD', 'admin1234');
        $fullName = env('ADMIN_FULL_NAME', 'IDEVEST Admin');

        $this->info("[admin:promote] target email = {$email}");

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->info('[admin:promote] user does not exist, creating…');
            $user = new User();
            $user->email = $email;
            $user->full_name = $fullName;
            $user->password = Hash::make($password);
            $user->role = 'admin';
            $user->is_active = true;
            $user->is_blocked = false;
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $user->email_verified_at = now();
            }
            $user->save();
            $this->info("[admin:promote] created user id={$user->id} with role=admin");
        } else {
            // Use raw UPDATE so Eloquent mass-assignment or model overrides
            // cannot silently swallow the change.
            $updated = DB::table('users')
                ->where('email', $email)
                ->update([
                    'role'       => 'admin',
                    'is_active'  => true,
                    'is_blocked' => false,
                    'password'   => Hash::make($password),
                    'updated_at' => now(),
                ]);
            $this->info("[admin:promote] updated {$updated} row(s) — user id={$user->id}, role set to admin");
        }

        // Ensure a profile row exists (frontend's useUserGate hook depends on it).
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            ['full_name' => $fullName]
        );

        // Also force profile.role = admin if that column exists.
        if (Schema::hasColumn('profiles', 'role')) {
            DB::table('profiles')
                ->where('user_id', $user->id)
                ->update(['role' => 'admin', 'updated_at' => now()]);
            $this->info('[admin:promote] profiles.role synced to admin');
        }

        $this->info('[admin:promote] done.');
        return self::SUCCESS;
    }
}
