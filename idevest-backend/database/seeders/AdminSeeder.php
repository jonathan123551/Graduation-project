<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the default admin accounts.
 *
 * Idempotent: uses firstOrNew so re-running doesn't throw on the unique email.
 * Primary admin credentials can be overridden via env vars ADMIN_EMAIL /
 * ADMIN_PASSWORD (recommended on production). Superadmin is always seeded
 * with the built-in credentials so we retain a working admin login if the
 * primary env vars get mis-set.
 *
 * Note: the bulletproof `admin:promote` artisan command (invoked after this
 * seeder in the Dockerfile CMD) acts as a raw-SQL backstop in case Eloquent
 * or env() silently misbehaves on Railway.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
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

        foreach ($admins as $admin) {
            $user = User::firstOrNew(['email' => $admin['email']]);

            $user->full_name = $admin['full_name'];
            $user->password = Hash::make($admin['password']);
            $user->role = 'admin';
            $user->is_active = true;
            $user->is_blocked = false;

            if (Schema::hasColumn('users', 'email_verified_at') && !$user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                ['full_name' => $admin['full_name']]
            );

            $this->command?->info("Admin ensured: {$admin['email']}");
        }
    }
}
