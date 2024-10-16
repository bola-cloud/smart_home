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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('quantity')->default(0);
            $table->boolean('activation')->default(0);
            $table->timestamp('last_updated')->nullable(); //date of activation
            $table->timestamps();
    
            $table->unsignedBigInteger('section_id')->nullable();
            $table->foreign('section_id')->references('id')->on('sections')
                ->onUpdate('CASCADE')->onDelete('SET NULL');
    
            // Ensure this is unsignedBigInteger to match the referenced table
            $table->unsignedBigInteger('device_type_id')->nullable();
            $table->foreign('device_type_id')->references('id')->on('device_types')
                ->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
