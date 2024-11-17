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
        Schema::table('components', function (Blueprint $table) {
            // Change the 'type' column to enum with default 'digital'
            $table->enum('type', ['analog', 'digital'])->default('digital')->change();

            // Add a new 'file_path' column
            $table->string('file_path')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            // Revert 'type' column back to its original state
            $table->string('type')->nullable()->change();

            // Drop the 'file_path' column
            $table->dropColumn('file_path');
        });
    }
};
