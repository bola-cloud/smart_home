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
        Schema::table('districts_lite', function (Blueprint $table) {
            // Add the new column with a default value of 0
            $table->integer('shipping')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('districts_lite', function (Blueprint $table) {
            // Drop the new column in case of rollback
            $table->dropColumn('shipping');
        });
    }
};
