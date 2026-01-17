<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        // 1) Add user_id (nullable first to avoid DBAL requirement)
        Schema::table('saved_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('saved_jobs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        // 2) Backfill user_id from candidates.user_id
        try {
            DB::statement('UPDATE saved_jobs sj JOIN candidates c ON sj.candidate_id = c.id SET sj.user_id = c.user_id WHERE sj.user_id IS NULL');
        } catch (\Throwable $e) {
            // Ignore if table empty or DB engine not supporting JOIN updates
        }

        // 3) Drop FK on candidate_id and then drop the column (this will also remove any indexes/unique using candidate_id)
        Schema::table('saved_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('saved_jobs', 'candidate_id')) {
                try {
                    $table->dropForeign(['candidate_id']);
                } catch (\Throwable $e) {
                    // continue even if FK name differs
                }
                try {
                    $table->dropColumn('candidate_id');
                } catch (\Throwable $e) {
                    // best effort; column may already be removed
                }
            }
        });

        // 4) Add new unique (user_id, job_id) only if not exists
        try {
            $exists = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', 'saved_jobs')
                ->where('index_name', 'saved_jobs_user_id_job_id_unique')
                ->exists();
            if (! $exists) {
                DB::statement('CREATE UNIQUE INDEX `saved_jobs_user_id_job_id_unique` ON `saved_jobs`(`user_id`, `job_id`)');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 5) Add FK for user_id => users(id) only if not exists
        try {
            $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'saved_jobs')
                ->where('COLUMN_NAME', 'user_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
            if (! $fkExists && Schema::hasColumn('saved_jobs', 'user_id')) {
                DB::statement('ALTER TABLE `saved_jobs` ADD CONSTRAINT `saved_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE');
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        // Reverse: re-add candidate_id, backfill from users->candidates, restore unique, drop user column/unique
        Schema::table('saved_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('saved_jobs', 'candidate_id')) {
                $table->unsignedBigInteger('candidate_id')->nullable()->after('id');
            }
        });

        // backfill candidate_id from users via candidates table
        try {
            DB::statement('UPDATE saved_jobs sj LEFT JOIN candidates c ON sj.user_id = c.user_id SET sj.candidate_id = c.id WHERE sj.candidate_id IS NULL');
        } catch (\Throwable $e) {
        }

        Schema::table('saved_jobs', function (Blueprint $table) {
            // drop new unique
            try {
                $table->dropUnique('saved_jobs_user_id_job_id_unique');
            } catch (\Throwable $e) {
            }
            // add back old unique
            try {
                $table->unique(['candidate_id', 'job_id'], 'saved_jobs_candidate_id_job_id_unique');
            } catch (\Throwable $e) {
            }
        });

        // drop FK user_id and user_id column
        Schema::table('saved_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('saved_jobs', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->dropColumn('user_id');
                } catch (\Throwable $e) {
                }
            }
        });

        // re-add FK candidate_id
        Schema::table('saved_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('saved_jobs', 'candidate_id')) {
                try {
                    $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
                } catch (\Throwable $e) {
                }
            }
        });
    }
};
