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
        Schema::create('region_lites', function (Blueprint $table) {
            $table->id('region_id');
            $table->unsignedBigInteger('capital_city_id');
            $table->string('code', 2)->default('');
            $table->string('name_ar', 64)->default('');
            $table->string('name_en', 64)->default('');
            $table->integer('population')->nullable();
    
            // Foreign key constraint
            $table->foreign('capital_city_id')->references('city_id')->on('city_lites')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_lites');
    }
};
