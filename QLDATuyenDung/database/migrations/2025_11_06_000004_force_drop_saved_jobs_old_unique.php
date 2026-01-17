<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        // Force drop the legacy unique index that still references old candidate-based schema
        try {
            DB::statement('ALTER TABLE `saved_jobs` DROP INDEX `saved_jobs_candidate_id_job_id_unique`');
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }
    }

    public function down(): void
    {
        // No rollback for legacy index
    }
};
