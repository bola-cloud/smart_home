<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('districts_lite', function (Blueprint $table) {
            $table->string('district_id', 12)->primary();
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('region_id');
            $table->string('name_ar', 64)->default('');
            $table->string('name_en', 64)->default('');
            $table->timestamps();
        });

        // Add the foreign key constraints
        Schema::table('cities_lite', function (Blueprint $table) {
            $table->foreign('region_id')->references('region_id')->on('regions_lite')->onDelete('cascade');
        });

        Schema::table('regions_lite', function (Blueprint $table) {
            $table->foreign('capital_city_id')->references('city_id')->on('cities_lite')->onDelete('cascade');
        });

        Schema::table('districts_lite', function (Blueprint $table) {
            $table->foreign('city_id')->references('city_id')->on('cities_lite')->onDelete('cascade');
            $table->foreign('region_id')->references('region_id')->on('regions_lite')->onDelete('cascade');
        });

    }




    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts_lite');
    }
};
