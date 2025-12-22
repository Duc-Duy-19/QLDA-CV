<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('resume_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('Full_name')->nullable();
            $table->date('BirthDay')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('Phone')->nullable();
            $table->string('Email')->nullable();
            $table->string('Website')->nullable();
            $table->text('address')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_headers');
    }
};
