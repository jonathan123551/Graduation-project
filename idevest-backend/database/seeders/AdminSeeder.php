<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds a default admin account.
 *
 * Idempotent: uses firstOrNew so re-running doesn't throw on the unique email.
 * Credentials can be overridden via env vars ADMIN_EMAIL / ADMIN_PASSWORD
 * (recommended on production deployments).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@idevest.com');
        $password = env('ADMIN_PASSWORD', 'admin1234');
        $fullName = env('ADMIN_FULL_NAME', 'IDEVEST Admin');

        $user = User::firstOrNew(['email' => $email]);

        // Keep the admin role + active flags correct even if the row already exists.
        $user->full_name = $fullName;
        $user->password = Hash::make($password);
        $user->role = 'admin';
        $user->is_active = true;
        $user->is_blocked = false;

        if (Schema::hasColumn('users', 'email_verified_at') && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        // Ensure a profile row exists so the frontend's useUserGate hook passes.
        Profile::firstOrCreate(
            ['user_id' => $user->id],
            ['full_name' => $fullName]
        );

        $this->command?->info("Admin ensured: {$email}");
    }
}
