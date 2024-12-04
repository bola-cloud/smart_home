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
        Schema::create('district_lites', function (Blueprint $table) {
            Schema::create('districts_lite', function (Blueprint $table) {
                $table->string('district_id', 12)->primary();
                $table->unsignedBigInteger('city_id');
                $table->unsignedBigInteger('region_id');
                $table->string('name_ar', 64)->default('');
                $table->string('name_en', 64)->default('');
        
                // Foreign key constraints (if needed)
                $table->foreign('city_id')->references('id')->on('cities_lite')->onDelete('cascade');
                $table->foreign('region_id')->references('region_id')->on('regions_lite')->onDelete('cascade');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_lites');
    }
};
