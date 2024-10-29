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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();  // ID of the user who made the change
            $table->string('action');  // 'create', 'update', 'delete'
            $table->string('model');   // The model or table affected
            $table->unsignedBigInteger('model_id');  // ID of the affected record
            $table->json('before_data')->nullable();  // Data before the change
            $table->json('after_data')->nullable();   // Data after the change
            $table->timestamps();

            // Optional: add a foreign key constraint for user_id if there's a users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
