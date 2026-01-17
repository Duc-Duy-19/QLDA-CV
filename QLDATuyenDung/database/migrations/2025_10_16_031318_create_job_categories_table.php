<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('job_category', function (Blueprint $table) {
            // ERD: Jobs_category with PK(id_job, id_category)
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->primary(['job_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_category');
    }
};
