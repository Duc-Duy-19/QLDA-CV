<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::table('applications', function (Blueprint $table) {
            // thêm user_id nếu chưa có
            if (! Schema::hasColumn('applications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('applications', 'applicant_name')) {
                $table->string('applicant_name')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('applications', 'applicant_email')) {
                $table->string('applicant_email')->nullable()->after('applicant_name');
            }

            if (! Schema::hasColumn('applications', 'resume_snapshot')) {
                $table->json('resume_snapshot')->nullable()->after('cv_file_url');
            }

            // NOTE: Không thay đổi cấu trúc candidate_id ở migration này để tránh phụ thuộc doctrine/dbal.
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'resume_snapshot')) {
                $table->dropColumn('resume_snapshot');
            }
            if (Schema::hasColumn('applications', 'applicant_email')) {
                $table->dropColumn('applicant_email');
            }
            if (Schema::hasColumn('applications', 'applicant_name')) {
                $table->dropColumn('applicant_name');
            }
            if (Schema::hasColumn('applications', 'user_id')) {
                // drop foreign then column
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
