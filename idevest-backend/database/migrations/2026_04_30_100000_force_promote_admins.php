<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Migration-as-deploy-hook: force-promote both built-in admin accounts
 * to role=admin with known passwords, on every fresh deploy of this
 * migration. Migrations run unconditionally during `php artisan migrate
 * --force`, so this guarantees the admin accounts work even if the
 * `admin:promote` artisan command somehow doesn't run from the
 * Dockerfile CMD chain (which has happened repeatedly on Railway).
 *
 * Idempotent: safely (re-)creates each user if missing and
 * (re-)applies the canonical password via raw SQL UPDATE so model
 * mutators / Eloquent boot listeners cannot interfere.
 */
return new class extends Migration {
    public function up(): void
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
            $this->promoteOne($admin);
        }
    }

    public function down(): void
    {
        // No-op — reversing this would lock admins out of their own DB.
    }

    protected function promoteOne(array $admin): void
    {
        $email    = $admin['email'];
        $password = $admin['password'];
        $fullName = $admin['full_name'];

        $existing = DB::table('users')->where('email', $email)->first();

        if (!$existing) {
            $userColumns = [
                'email'      => $email,
                'full_name'  => $fullName,
                'password'   => Hash::make($password),
                'role'       => 'admin',
                'is_active'  => true,
                'is_blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $userColumns['email_verified_at'] = now();
            }

            $userId = DB::table('users')->insertGetId($userColumns);
            echo "[force_promote_admins] created user id={$userId} email={$email}\n";
        } else {
            $userId = $existing->id;
            $updated = DB::table('users')
                ->where('email', $email)
                ->update([
                    'role'       => 'admin',
                    'is_active'  => true,
                    'is_blocked' => false,
                    'password'   => Hash::make($password),
                    'updated_at' => now(),
                ]);
            echo "[force_promote_admins] updated user id={$userId} email={$email} ({$updated} row(s))\n";
        }

        // Profile row — required by the frontend's user-gate hook.
        $existingProfile = DB::table('profiles')->where('user_id', $userId)->first();
        if (!$existingProfile) {
            $profileColumns = [
                'user_id'    => $userId,
                'full_name'  => $fullName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('profiles', 'role')) {
                $profileColumns['role'] = 'admin';
            }
            DB::table('profiles')->insert($profileColumns);
            echo "[force_promote_admins] created profile for user id={$userId}\n";
        } else {
            if (Schema::hasColumn('profiles', 'role')) {
                DB::table('profiles')
                    ->where('user_id', $userId)
                    ->update(['role' => 'admin', 'updated_at' => now()]);
                echo "[force_promote_admins] profiles.role synced to admin for user id={$userId}\n";
            }
        }
    }
};
