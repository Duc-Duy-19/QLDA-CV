<?php

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
        Schema::create('messages', function (Blueprint $table) {
            $table->id('id__message');
            $table->foreignId('id_conversa_FK')->constrained('conversations', 'id_conversa')->onDelete('cascade');
            $table->foreignId('id_user_FK')->constrained('users', 'id')->onDelete('cascade');
            $table->text('content');
            $table->timestamp('send_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
