<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `ideas.decision` column was originally created as
 *   enum('INVEST', 'WATCH', 'PASS')
 * which adds a CHECK constraint on every backend (sqlite + Postgres).
 *
 * Frontend now writes lowercase values ('accepted', 'needs_improvement',
 * 'rejected'), so the CHECK is rejecting valid writes — submissions to
 * /api/ideas crash with "CHECK constraint failed: decision" on sqlite and
 * "ideas_decision_check" on Postgres.
 *
 * Strip the CHECK constraint on every driver. PR #15 already handled
 * Postgres ideas_decision_check, but sqlite still has it inline in the
 * sqlite_master DDL — patch the schema there too.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite' && Schema::hasTable('ideas')) {
            try {
                DB::statement('PRAGMA writable_schema = 1');
                DB::statement("UPDATE sqlite_master SET sql = REPLACE(sql, ' check (\"decision\" in (''INVEST'', ''WATCH'', ''PASS''))', '') WHERE type = 'table' AND name = 'ideas'");
                DB::statement('PRAGMA writable_schema = 0');
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        if ($driver === 'pgsql' && Schema::hasTable('ideas')) {
            try {
                DB::statement('ALTER TABLE ideas DROP CONSTRAINT IF EXISTS ideas_decision_check');
                DB::statement('ALTER TABLE ideas ALTER COLUMN decision TYPE VARCHAR(50) USING decision::text');
            } catch (\Throwable $e) {
                // already widened
            }
        }
    }

    public function down(): void
    {
        // repair migration — no-op rollback
    }
};
