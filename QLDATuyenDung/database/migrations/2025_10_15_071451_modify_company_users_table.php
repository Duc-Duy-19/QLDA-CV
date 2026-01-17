<?php
// filepath: c:\laragon\www\websitetuyendungthucte\webcv\database\migrations\2025_10_15_071451_modify_company_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            // 1. Xóa foreign key constraint cũ
            $table->dropForeign(['user_id']);

            // 2. Thay đổi user_id thành nullable
            $table->foreignId('user_id')->nullable()->change();

            // 3. Thêm lại foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // 4. Thêm unique constraint cho email (nếu chưa có)
            try {
                $table->unique(['company_id', 'email'], 'company_email_unique');
            } catch (\Exception $e) {
                // Bỏ qua nếu constraint đã tồn tại
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            // Xóa foreign key
            $table->dropForeign(['user_id']);

            // Xóa unique constraint cho email
            try {
                $table->dropUnique('company_email_unique');
            } catch (\Exception $e) {
                // Bỏ qua nếu constraint không tồn tại
            }

            // Trả về user_id not null
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
