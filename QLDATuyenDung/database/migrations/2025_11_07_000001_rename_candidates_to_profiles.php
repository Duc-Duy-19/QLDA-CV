<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        // Đổi tên bảng candidates thành profiles (chỉ nếu bảng candidates tồn tại và profiles chưa tồn tại)
        if (Schema::hasTable('candidates') && !Schema::hasTable('profiles')) {
            Schema::rename('candidates', 'profiles');
        }

        // Cập nhật foreign key trong bảng resumes (chỉ nếu có cột candidate_id)
        if (Schema::hasColumn('resumes', 'candidate_id')) {
            // Tìm tên foreign key thực tế
            $fkName = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'resumes' 
                AND COLUMN_NAME = 'candidate_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ");

            Schema::table('resumes', function (Blueprint $table) use ($fkName) {
                if ($fkName) {
                    $table->dropForeign([$fkName->CONSTRAINT_NAME]);
                }
                $table->renameColumn('candidate_id', 'profile_id');
                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            });
        }

        // Cập nhật foreign key trong bảng applications
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'candidate_id')) {
                $table->dropForeign(['candidate_id']);
                $table->renameColumn('candidate_id', 'profile_id');
                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        // Đổi ngược lại foreign key trong applications
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'profile_id')) {
                $table->dropForeign(['profile_id']);
                $table->renameColumn('profile_id', 'candidate_id');
                $table->foreign('candidate_id')->references('id')->on('profiles')->onDelete('set null');
            }
        });

        // Đổi ngược lại foreign key trong resumes (chỉ nếu có cột profile_id)
        if (Schema::hasColumn('resumes', 'profile_id')) {
            Schema::table('resumes', function (Blueprint $table) {
                try {
                    $table->dropForeign(['resumes_profile_id_foreign']);
                } catch (\Exception $e) {
                    // Bỏ qua nếu không tìm thấy foreign key
                }
                $table->renameColumn('profile_id', 'candidate_id');
                $table->foreign('candidate_id')->references('id')->on('profiles')->onDelete('cascade');
            });
        }

        // Đổi ngược lại tên bảng
        Schema::rename('profiles', 'candidates');
    }
};
