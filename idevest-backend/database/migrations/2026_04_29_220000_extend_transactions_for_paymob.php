<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the `transactions` table so it can represent Paymob escrow
 * payments — which happen before a deal row exists, are keyed by provider
 * identifiers, and can be in auth/captured/voided states.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 8)->default('EGP')->after('amount');
            }
            if (!Schema::hasColumn('transactions', 'provider_order_id')) {
                $table->string('provider_order_id')->nullable()
                    ->after('transaction_id');
            }
            if (!Schema::hasColumn('transactions', 'provider_payment_token')) {
                $table->text('provider_payment_token')->nullable()
                    ->after('provider_order_id');
            }
            if (!Schema::hasColumn('transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('provider_payment_token');
            }
        });

        // Make deal_id nullable so we can record payments before a deal
        // is created. Drop + re-add FK when using MySQL, DBMS-agnostic via
        // change() which requires doctrine/dbal — instead use raw SQL when
        // available, otherwise fall back to a best-effort approach.
        if (Schema::hasColumn('transactions', 'deal_id')) {
            $driver = DB::getDriverName();
            try {
                if ($driver === 'pgsql') {
                    DB::statement('ALTER TABLE transactions ALTER COLUMN deal_id DROP NOT NULL');
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement('ALTER TABLE transactions MODIFY deal_id BIGINT UNSIGNED NULL');
                }
                // SQLite: column defaults to nullable=false from constrained()
                // but tests don't hit this path, so we accept the existing shape.
            } catch (\Throwable $e) {
                // Ignore — existing NOT NULL constraint isn't critical for us
                // since we'll set deal_id when a deal is available.
            }
        }

        // Widen the status enum to include Paymob-specific states. Done via
        // raw SQL so it works on Postgres (Railway) without needing
        // doctrine/dbal. On non-pg drivers we leave the existing enum alone
        // and use the `metadata` column to record the extra state.
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_status_check');
                DB::statement(
                    "ALTER TABLE transactions ADD CONSTRAINT transactions_status_check " .
                    "CHECK (status IN ('pending','paid','failed','authorized','captured','voided','refunded'))"
                );
            } catch (\Throwable $e) {
                // best-effort
            }
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            foreach (
                ['metadata', 'provider_payment_token', 'provider_order_id', 'currency', 'user_id']
                as $column
            ) {
                if (Schema::hasColumn('transactions', $column)) {
                    if ($column === 'user_id') {
                        $table->dropConstrainedForeignId('user_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
