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
            // Add a temporary enum column
            $table->enum('type_enum', ['analog', 'digital'])->default('digital')->after('type');

            // Update the new column with valid data from the old column
            DB::statement("UPDATE components SET type_enum = IF(type NOT IN ('analog', 'digital'), 'digital', type)");

            // Drop the old column and rename the new one
            $table->dropColumn('type');
            $table->renameColumn('type_enum', 'type');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            // Revert to a string type for the type column
            $table->string('type')->nullable()->after('name');
        });
    }
};
