<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::table('applications', function (Blueprint $table) {
            // Lưu snapshot của resume (JSON). Dùng text nếu DB không hỗ trợ json.
            $table->text('resume_snapshot')->nullable()->after('resume_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('resume_snapshot');
        });
    }
};
