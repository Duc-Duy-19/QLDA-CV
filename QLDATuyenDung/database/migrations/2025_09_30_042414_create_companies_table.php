<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        //
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // ERD: id_company
            $table->string('company_name');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->longText('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
