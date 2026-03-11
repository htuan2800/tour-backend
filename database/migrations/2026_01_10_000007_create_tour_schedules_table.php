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
        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('tour_id');
            $table->date('departure_date');
            $table->date('return_date');
            $table->decimal('price_adult', 15, 2);
            $table->decimal('price_child', 15, 2);
            $table->integer('max_capacity')->default(40);
            $table->integer('current_booked')->default(0);
            $table->enum('status', ['OPEN', 'CLOSED', 'CANCELLED', 'COMPLETED'])->default('OPEN');

            $table->foreign('tour_id')->references('tour_id')->on('tours')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_schedules');
    }
};
