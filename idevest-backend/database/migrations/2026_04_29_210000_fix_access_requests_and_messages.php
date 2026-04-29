<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent repair migration for two tables created by earlier, incomplete
 * migrations:
 *
 *  - `access_requests`: original migration only created `id` + `timestamps`,
 *    missing every real column (investor_id, founder_id, idea_id, status,
 *    message). This broke `GET /api/access-requests` on production with a 500.
 *
 *  - `messages`: original migration created column `is_read` (boolean) but
 *    `MessageController` reads/writes `read` + `read_at`. The frontend also
 *    expects `read`. Rename `is_read` -> `read` if needed, add `read_at`.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── access_requests ───────────────────────────────────────────────
        if (Schema::hasTable('access_requests')) {
            Schema::table('access_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('access_requests', 'investor_id')) {
                    $table->unsignedBigInteger('investor_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('access_requests', 'founder_id')) {
                    $table->unsignedBigInteger('founder_id')->nullable()->after('investor_id');
                }
                if (!Schema::hasColumn('access_requests', 'idea_id')) {
                    $table->unsignedBigInteger('idea_id')->nullable()->after('founder_id');
                }
                if (!Schema::hasColumn('access_requests', 'status')) {
                    $table->string('status', 32)->default('pending')->after('idea_id');
                }
                if (!Schema::hasColumn('access_requests', 'message')) {
                    $table->text('message')->nullable()->after('status');
                }
            });

            // Best-effort indexes (skip if already present).
            try {
                Schema::table('access_requests', function (Blueprint $table) {
                    $table->index('investor_id');
                });
            } catch (\Throwable $e) {
                // index already exists — ignore
            }
            try {
                Schema::table('access_requests', function (Blueprint $table) {
                    $table->index('founder_id');
                });
            } catch (\Throwable $e) {
                // index already exists — ignore
            }
        }

        // ── messages ──────────────────────────────────────────────────────
        if (Schema::hasTable('messages')) {
            // rename is_read -> read (if is_read exists and read doesn't)
            if (Schema::hasColumn('messages', 'is_read') && !Schema::hasColumn('messages', 'read')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->renameColumn('is_read', 'read');
                });
            }
            // make sure `read` exists even on older DBs that had neither
            if (!Schema::hasColumn('messages', 'read')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->boolean('read')->default(false);
                });
            }
            // add read_at if missing
            if (!Schema::hasColumn('messages', 'read_at')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->timestamp('read_at')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        // This migration is a repair; no-op rollback to avoid data loss.
    }
};
