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
        Schema::create('room_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id'); // Foreign key linking to Room
            $table->string('name');               // Device name (e.g., Mini R2)
            $table->integer('quantity');          // Number of devices
            $table->decimal('unit_price', 10, 2); // Price per device
            $table->decimal('total_price', 10, 2); // Calculated total price
            $table->timestamps();
    
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_devices');
    }
};
