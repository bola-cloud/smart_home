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
        Schema::create('actions', function (Blueprint $table) {
            $table->id(); // action_id (Primary Key)
            $table->foreignId('device_id')->constrained()->onDelete('cascade'); // Foreign Key referencing devices
            $table->foreignId('component_id')->constrained()->onDelete('cascade'); // Foreign Key referencing components
            $table->enum('action_type', ['digital', 'analog']);
            $table->string('status')->nullable(); // For digital actions
            $table->json('json_data')->nullable(); // For analog actions
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
