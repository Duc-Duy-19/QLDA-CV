<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {

        // Drop the leftover unique index that referenced candidate_id (now dropped) but may have been reduced to job_id
        try {
            DB::statement('ALTER TABLE `saved_jobs` DROP INDEX `saved_jobs_candidate_id_job_id_unique`');
        } catch (\Throwable $e) {
            // Ignore if it does not exist
        }

        // Ensure unique on (user_id, job_id) exists (check information_schema to avoid duplicate key error)
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
        // Drop the (user_id, job_id) unique; do not attempt to re-create the old candidate-based index
        Schema::table('saved_jobs', function (Blueprint $table) {
            try {
                $table->dropUnique('saved_jobs_user_id_job_id_unique');
            } catch (\Throwable $e) {
            }
        });
    }
};
