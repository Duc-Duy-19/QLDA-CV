<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        // 1) Drop any leftover UNIQUE indexes on saved_jobs except PRIMARY and the correct (user_id, job_id)
        try {
            $indexes = DB::table('information_schema.statistics')
                ->select('index_name')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', 'saved_jobs')
                ->where('non_unique', 0) // unique indexes (including PRIMARY)
                ->whereNotIn('index_name', ['PRIMARY', 'saved_jobs_user_id_job_id_unique'])
                ->distinct()
                ->pluck('index_name')
                ->toArray();

            foreach ($indexes as $idx) {
                try {
                    DB::statement("ALTER TABLE `saved_jobs` DROP INDEX `{$idx}`");
                } catch (\Throwable $e) {
                    // ignore and continue
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Ensure the correct unique (user_id, job_id) exists
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
    }

    public function down(): void
    {
        // No-op: we won't re-create old invalid indexes
        // Optionally drop the (user_id, job_id) unique on rollback
        try {
            DB::statement('ALTER TABLE `saved_jobs` DROP INDEX `saved_jobs_user_id_job_id_unique`');
        } catch (\Throwable $e) {
        }
    }
};
