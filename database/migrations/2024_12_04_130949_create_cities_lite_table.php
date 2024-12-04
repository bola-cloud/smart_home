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
        Schema::create('cities_lite', function (Blueprint $table) {
            $table->id('city_id');
            $table->unsignedBigInteger('region_id');
            $table->string('name_ar', 64)->default('');
            $table->string('name_en', 64)->default('');
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities_lite');
    }
};
