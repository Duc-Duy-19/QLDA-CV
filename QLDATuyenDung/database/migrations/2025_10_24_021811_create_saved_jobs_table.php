<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id(); // ERD: id_saved
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade'); // id_uv FK
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade'); // id_job FK
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};
