<?php

namespace App\Console\Commands;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Force-promote one or more emails to the admin role.
 *
 * Provides a no-op-safe, bulletproof way to ensure every required admin
 * account exists with `role='admin'` on every deploy. Unlike AdminSeeder
 * (which relies on env() + Eloquent mass assignment — both have silently
 * failed in Railway deploys), this uses raw DB::table(...)->update(...)
 * and logs every step visibly in the deploy logs.
 *
 * When invoked with no arguments, it seeds the built-in default admin
 * list (primary admin + superadmin) so we always have a working login
 * even if the Railway DB gets wiped or the main seeder silently failed.
 *
 * Usage:
 *   php artisan admin:promote                             # seeds default admin list
 *   php artisan admin:promote foo@bar.com secret          # promote one user explicitly
 */
class PromoteAdmin extends Command
{
    protected $signature = 'admin:promote
        {email? : Email of the user to promote (defaults to the built-in admin list)}
        {password? : Optional password to (re)set (defaults to env or built-in)}';

    protected $description = 'Force-promote user(s) to admin role via raw DB update (bulletproof seeder alternative).';

    /**
     * Built-in list of admins that must always exist on every deploy.
     *
     * Ordering matters: env-overridden primary first, then static superadmin.
     */
    protected function defaultAdmins(): array
    {
        return [
            [
                'email'     => env('ADMIN_EMAIL', 'admin@idevest.com'),
                'password'  => env('ADMIN_PASSWORD', 'admin1234'),
                'full_name' => env('ADMIN_FULL_NAME', 'IDEVEST Admin'),
            ],
            [
                'email'     => 'superadmin@idevest.com',
                'password'  => 'Super1234!',
                'full_name' => 'IDEVEST Super Admin',
            ],
        ];
    }

    public function handle(): int
    {
        $explicitEmail = $this->argument('email');

        if ($explicitEmail !== null) {
            $this->promoteOne([
                'email'     => $explicitEmail,
                'password'  => $this->argument('password') ?? env('ADMIN_PASSWORD', 'admin1234'),
                'full_name' => env('ADMIN_FULL_NAME', 'IDEVEST Admin'),
            ]);
            $this->info('[admin:promote] done.');
            return self::SUCCESS;
        }

        foreach ($this->defaultAdmins() as $admin) {
            $this->promoteOne($admin);
        }

        $this->info('[admin:promote] done.');
        return self::SUCCESS;
    }

    protected function promoteOne(array $admin): void
    {
        $email    = $admin['email'];
        $password = $admin['password'];
        $fullName = $admin['full_name'];

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
            // Raw UPDATE so Eloquent mass-assignment or model overrides
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
        Profile::firstOrCreate(
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
    }
}
