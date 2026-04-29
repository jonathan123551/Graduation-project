<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Align the `ideas` and `profiles` tables with what the Laravel controllers
 * and the frontend actually read/write.
 *
 * This migration is idempotent — every check is guarded by Schema::hasColumn
 * so it's safe to re-run on existing Railway databases.
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // ── ideas ────────────────────────────────────────────────────────────
        if (Schema::hasTable('ideas')) {
            if (Schema::hasColumn('ideas', 'user_id') && !Schema::hasColumn('ideas', 'founder_id')) {
                Schema::table('ideas', fn (Blueprint $t) => $t->renameColumn('user_id', 'founder_id'));
            }

            if (Schema::hasColumn('ideas', 'project_name') && !Schema::hasColumn('ideas', 'title')) {
                Schema::table('ideas', fn (Blueprint $t) => $t->renameColumn('project_name', 'title'));
            }

            Schema::table('ideas', function (Blueprint $t) {
                if (!Schema::hasColumn('ideas', 'additional_info')) {
                    $t->text('additional_info')->nullable();
                }
                if (!Schema::hasColumn('ideas', 'document_url')) {
                    $t->string('document_url')->nullable();
                }
                if (!Schema::hasColumn('ideas', 'pitch_deck_url')) {
                    $t->string('pitch_deck_url')->nullable();
                }
                if (!Schema::hasColumn('ideas', 'capital_required_usd')) {
                    $t->decimal('capital_required_usd', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('ideas', 'evaluation_version')) {
                    $t->string('evaluation_version')->nullable();
                }
            });

            // Expand `status` from enum(pending,approved,rejected) to free-form
            // string so we can also store 'published' etc.
            if ($driver === 'pgsql') {
                try {
                    DB::statement('ALTER TABLE ideas ALTER COLUMN status TYPE VARCHAR(50)');
                } catch (\Throwable $e) {
                    // already altered; ignore
                }
                try {
                    DB::statement("ALTER TABLE ideas ALTER COLUMN status SET DEFAULT 'pending'");
                } catch (\Throwable $e) {
                    // ignore
                }
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                try {
                    DB::statement("ALTER TABLE ideas MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            // sqlite is dynamically typed — nothing to do for status.
        }

        // ── profiles ─────────────────────────────────────────────────────────
        if (Schema::hasTable('profiles')) {
            // Rename legacy `avatar` column to `avatar_url` if present.
            if (Schema::hasColumn('profiles', 'avatar') && !Schema::hasColumn('profiles', 'avatar_url')) {
                Schema::table('profiles', fn (Blueprint $t) => $t->renameColumn('avatar', 'avatar_url'));
            }

            Schema::table('profiles', function (Blueprint $t) {
                if (!Schema::hasColumn('profiles', 'avatar_url')) {
                    $t->string('avatar_url')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'bio')) {
                    $t->text('bio')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'location')) {
                    $t->string('location')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'linkedin_url')) {
                    $t->string('linkedin_url')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'skills')) {
                    // json() works on pgsql/mysql/sqlite (as TEXT).
                    $t->json('skills')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-reversible: we don't want to drop user data.
    }
};
