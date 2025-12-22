<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cập nhật các giá trị cũ sang giá trị mới

        DB::table('applications')
            ->where('status', 'hired')
            ->update(['status' => 'approved']);

        DB::table('applications')
            ->where('status', 'interview')
            ->update(['status' => 'reviewed']);

        // Sửa lại enum
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'reviewed', 'approved', 'rejected') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Khôi phục lại enum cũ
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'reviewed', 'interview', 'rejected', 'hired') DEFAULT 'pending'");

        // Khôi phục giá trị cũ
        DB::table('applications')
            ->where('status', 'approved')
            ->update(['status' => 'hired']);
    }
};
