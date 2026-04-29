<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns the application expects on the users table, and migrate the
     * legacy `name` column (from the Laravel skeleton) into `full_name`.
     *
     * This migration is idempotent. On a fresh install where the baseline
     * users-table migration already creates the correct columns, it does
     * nothing. On existing deployments created before the baseline was
     * fixed, it adds the missing columns and drops `name`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('explorer')->after('password');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }
        });

        if (Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->whereNull('full_name')
                ->update(['full_name' => DB::raw('name')]);

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        // Reversing this migration would lose data; intentionally a no-op.
    }
};
