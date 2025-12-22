<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        // Bỏ unique để cho phép re-apply sau cooldown
        if ($this->indexExists('applications', 'applications_job_user_unique')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropUnique('applications_job_user_unique');
            });
        }
        if ($this->indexExists('applications', 'applications_job_candidate_unique')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropUnique('applications_job_candidate_unique');
            });
        }

        // Thêm index thường để tối ưu truy vấn kiểm tra
        if (! $this->indexExists('applications', 'applications_job_user_index')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->index(['job_id', 'user_id'], 'applications_job_user_index');
            });
        }
        if (! $this->indexExists('applications', 'applications_job_candidate_index')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->index(['job_id', 'candidate_id'], 'applications_job_candidate_index');
            });
        }
    }

    public function down(): void
    {
        // Xóa index thường
        if ($this->indexExists('applications', 'applications_job_user_index')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropIndex('applications_job_user_index');
            });
        }
        if ($this->indexExists('applications', 'applications_job_candidate_index')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropIndex('applications_job_candidate_index');
            });
        }

        // Tạo lại unique như cũ (nếu muốn revert)
        if (! $this->indexExists('applications', 'applications_job_user_unique')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unique(['job_id', 'user_id'], 'applications_job_user_unique');
            });
        }
        if (! $this->indexExists('applications', 'applications_job_candidate_unique')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unique(['job_id', 'candidate_id'], 'applications_job_candidate_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );
        return isset($result[0]) && (int)$result[0]->cnt > 0;
    }
};
