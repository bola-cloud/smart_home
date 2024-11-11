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
        Schema::create('job_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('job_id'); // Store the job ID
            $table->unsignedBigInteger('condition_id'); // Reference to the condition
            $table->string('status')->default('queued');
            $table->string('case_id');
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('condition_id')->references('id')->on('conditions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_trackers');
    }
};
