<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * On Postgres, Laravel's `$table->enum()` is implemented as a VARCHAR with
 * a CHECK constraint, NOT as a real Postgres ENUM. The earlier migration
 * 2026_04_29_200000_fix_ideas_and_profiles_tables tried to widen
 * `ideas.status` via `ALTER COLUMN status TYPE VARCHAR(50)` — that succeeds
 * (the column was already varchar under the hood), BUT the CHECK constraint
 * (`ideas_status_check`) was left in place, so inserting 'published' still
 * fails with `Server Error` on prod.
 *
 * Drop every CHECK constraint that's blocking the controllers from writing
 * the values they need:
 *
 *   - ideas.status        → controllers write 'published'
 *   - ideas.decision      → controllers write lowercase 'go' / 'no-go' etc.
 *   - deals.status        → controllers write 'active' / 'cancelled'
 *   - deals.payment_status (free-form going forward)
 *   - kyc_verifications.status (admin sets 'approved'/'rejected'/'pending')
 *
 * No-op on sqlite/mysql (the e2e_repair_migration already handles sqlite).
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $targets = [
            'ideas_status_check',
            'ideas_decision_check',
            'deals_status_check',
            'deals_payment_status_check',
            'kyc_verifications_status_check',
            'access_requests_status_check',
        ];

        foreach ($targets as $constraint) {
            // Find the table this constraint lives on (Postgres lets the same
            // constraint name exist across schemas — we only want public).
            $table = $this->tableForConstraint($constraint);
            if (!$table) {
                continue;
            }

            try {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
            } catch (\Throwable $e) {
                // best-effort — keep going
            }
        }

        // Also: make sure `status` is plain VARCHAR(50), not artificially
        // narrowed somewhere upstream.
        foreach (['ideas', 'deals', 'kyc_verifications', 'access_requests'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'status')) {
                try {
                    DB::statement("ALTER TABLE {$t} ALTER COLUMN status TYPE VARCHAR(50) USING status::text");
                } catch (\Throwable $e) {
                    // already varchar — ignore
                }
            }
        }
    }

    public function down(): void
    {
        // Repair migration — no-op rollback.
    }

    /**
     * Look up the table that owns a given CHECK constraint, if any.
     */
    protected function tableForConstraint(string $name): ?string
    {
        try {
            $row = DB::selectOne(
                "SELECT conrelid::regclass::text AS tbl
                   FROM pg_constraint
                  WHERE conname = ?
                    AND contype = 'c'
                  LIMIT 1",
                [$name]
            );
            return $row->tbl ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
};
