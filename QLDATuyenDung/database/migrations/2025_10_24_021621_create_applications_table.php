<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('applications', function (Blueprint $table) {
            $table->id(); // ERD: id_application
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade'); // id_uv FK
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade'); // id_job FK
            $table->foreignId('resume_id')->nullable()->constrained('resumes')->nullOnDelete(); // id_resume FK
            $table->enum('status', ['pending', 'reviewed', 'interview', 'rejected', 'hired'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->string('cv_file_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
