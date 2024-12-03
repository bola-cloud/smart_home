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
        Schema::table('checkouts', function (Blueprint $table) {
            $table->string('code')->nullable()->unique(); // Ensure code is unique
        });
    }
    
    public function down()
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropUnique(['code']); // Drop the unique constraint if rolled back
        });
    }
    
};
