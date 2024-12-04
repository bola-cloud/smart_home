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
        Schema::create('city_lites', function (Blueprint $table) {
            $table->id('city_id');
            $table->unsignedBigInteger('region_id');
            $table->string('name_ar', 64)->default('');
            $table->string('name_en', 64)->default('');
    
            // Foreign key constraint
            $table->foreign('region_id')->references('region_id')->on('regions_lite')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_lites');
    }
};
