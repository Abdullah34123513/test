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
        Schema::create('command_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('command');
            $table->string('status')->default('pending'); // pending, delivered, executed, failed
            $table->json('payload')->nullable();
            $table->string('response_message')->nullable(); // Error or success message from device
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_logs');
    }
};
