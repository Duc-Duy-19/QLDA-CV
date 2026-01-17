<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('resume_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('contact_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_references');
    }
};
