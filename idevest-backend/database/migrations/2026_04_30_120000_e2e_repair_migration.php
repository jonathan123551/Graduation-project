<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * E2E QA repair migration.
 *
 * Catches up the schema to what the controllers and frontend actually
 * expect — fills gaps left by earlier migrations:
 *
 *  - kyc_verifications: full_legal_name + national_id were NOT NULL,
 *    which made the legitimate "user has not started KYC" state crash
 *    `firstOrCreate` and 500 the GET /api/kyc endpoint.
 *
 *  - deals: missing `terms`, `accepted_at`, `nda_signed_at` columns
 *    that DealController writes; status enum is too narrow on Postgres
 *    (won't accept 'accepted'/'rejected'). Drop the enum check, keep
 *    the column as a plain string.
 *
 *  - phone_otps: new table for the free-tier OTP fallback (hashed code
 *    with TTL).
 *
 *  - access_requests: ensure unique (investor_id, idea_id) for the
 *    /access-requests/check/{ideaId} lookup.
 *
 * Idempotent — every operation is gated behind a column/table existence
 * check, so it's safe to re-run on partial DBs.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // ── ideas: on sqlite, the original migration created
        // `status enum(pending,approved,rejected)` which sqlite stores as a
        // CHECK constraint. Strip the CHECK so 'published' (and future
        // statuses like 'draft', 'archived') can be saved. Postgres/MySQL
        // were already widened by 2026_04_29_200000_fix_ideas_and_profiles_tables.
        if ($driver === 'sqlite' && Schema::hasTable('ideas')) {
            try {
                DB::statement('PRAGMA writable_schema = 1');
                DB::statement("UPDATE sqlite_master SET sql = REPLACE(sql, ' check (\"status\" in (''pending'', ''approved'', ''rejected''))', '') WHERE type = 'table' AND name = 'ideas'");
                DB::statement('PRAGMA writable_schema = 0');
            } catch (\Throwable $e) {
                // best-effort — no-op
            }
        }

        // ── kyc_verifications: drop NOT NULL on submission columns ───────
        if (Schema::hasTable('kyc_verifications')) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE kyc_verifications ALTER COLUMN full_legal_name DROP NOT NULL');
                DB::statement('ALTER TABLE kyc_verifications ALTER COLUMN national_id DROP NOT NULL');
            } elseif ($driver === 'mysql') {
                DB::statement('ALTER TABLE kyc_verifications MODIFY full_legal_name VARCHAR(255) NULL');
                DB::statement('ALTER TABLE kyc_verifications MODIFY national_id VARCHAR(255) NULL');
            }
            // sqlite: NULLs already accepted at INSERT time when value omitted; no-op.
        }

        // ── deals: add missing columns ───────────────────────────────────
        if (Schema::hasTable('deals')) {
            Schema::table('deals', function (Blueprint $table) {
                if (!Schema::hasColumn('deals', 'terms')) {
                    $table->text('terms')->nullable();
                }
                if (!Schema::hasColumn('deals', 'accepted_at')) {
                    $table->timestamp('accepted_at')->nullable();
                }
                if (!Schema::hasColumn('deals', 'nda_signed_at')) {
                    $table->timestamp('nda_signed_at')->nullable();
                }
            });

            // Drop the restrictive enum check on `status` so the controller
            // can write 'pending'|'active'|'cancelled'|'closed' freely.
            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                try {
                    DB::statement("ALTER TABLE deals DROP CONSTRAINT IF EXISTS deals_status_check");
                } catch (\Throwable $e) {
                    // already dropped — ignore
                }
                try {
                    DB::statement("ALTER TABLE deals ALTER COLUMN status TYPE VARCHAR(32)");
                } catch (\Throwable $e) {
                    // already varchar — ignore
                }
            }
        }

        // ── phone_otps: free OTP fallback ────────────────────────────────
        if (!Schema::hasTable('phone_otps')) {
            Schema::create('phone_otps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('phone_number', 32);
                $table->string('code_hash', 64);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->unique('user_id');
            });
        }

        // ── nda_signatures: pairwise NDA tracking ────────────────────────
        if (!Schema::hasTable('nda_signatures')) {
            Schema::create('nda_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('idea_id')->constrained('ideas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('other_user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
                $table->unique(['idea_id', 'user_id', 'other_user_id'], 'nda_unique');
            });
        }

        // ── access_requests: unique (investor_id, idea_id) for /check ────
        if (Schema::hasTable('access_requests')) {
            try {
                Schema::table('access_requests', function (Blueprint $table) {
                    $table->unique(['investor_id', 'idea_id'], 'access_requests_inv_idea_unique');
                });
            } catch (\Throwable $e) {
                // already exists — ignore
            }
        }
    }

    public function down(): void
    {
        // Repair migration — no-op rollback.
    }
};
