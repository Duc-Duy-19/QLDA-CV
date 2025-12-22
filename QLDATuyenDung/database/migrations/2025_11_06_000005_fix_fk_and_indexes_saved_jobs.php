<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {

        // 1) Ensure there is a standalone index on job_id (FK requires a leftmost index)
        try {
            $hasJobIdx = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = DATABASE()')
                ->where('table_name', 'saved_jobs')
                ->where('index_name', 'saved_jobs_job_id_index')
                ->exists();
            if (! $hasJobIdx) {
                DB::statement('CREATE INDEX `saved_jobs_job_id_index` ON `saved_jobs`(`job_id`)');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 2) Drop any FK on job_id so we can drop the legacy unique index safely
        try {
            $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->select('CONSTRAINT_NAME')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'saved_jobs')
                ->where('COLUMN_NAME', 'job_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->pluck('CONSTRAINT_NAME');

            foreach ($constraints as $cname) {
                try {
                    DB::statement("ALTER TABLE `saved_jobs` DROP FOREIGN KEY `{$cname}`");
                } catch (\Throwable $e) {
                    // continue
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 3) Drop the lingering unique index based on old candidate schema (now only on job_id)
        try {
            DB::statement('ALTER TABLE `saved_jobs` DROP INDEX `saved_jobs_candidate_id_job_id_unique`');
        } catch (\Throwable $e) {
            // ignore if already gone
        }

        // 4) Recreate FK on job_id -> jobs(id) with ON DELETE CASCADE
        try {
            DB::statement('ALTER TABLE `saved_jobs` ADD CONSTRAINT `saved_jobs_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }

    public function down(): void
    {
        // Down: best-effort remove the recreated FK and the helper index on job_id
        try {
            DB::statement('ALTER TABLE `saved_jobs` DROP FOREIGN KEY `saved_jobs_job_id_foreign`');
        } catch (\Throwable $e) {
        }
        try {
            DB::statement('DROP INDEX `saved_jobs_job_id_index` ON `saved_jobs`');
        } catch (\Throwable $e) {
        }
    }
};
