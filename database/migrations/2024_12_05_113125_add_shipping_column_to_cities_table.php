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
        Schema::table('cities', function (Blueprint $table) {
            // Add the shipping column with a default value of 0 (could be integer or boolean as needed)
            $table->boolean('shipping')->default(0);  // Default value 0 for no shipping
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            // Drop the shipping column
            $table->dropColumn('shipping');
        });
    }
};
