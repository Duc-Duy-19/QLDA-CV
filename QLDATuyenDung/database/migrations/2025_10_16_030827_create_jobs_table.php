<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); // ERD: id_job
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); // id_company FK
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('salary_range')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();
            $table->timestamp('posted_date')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
