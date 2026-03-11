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
        Schema::create('tours', function (Blueprint $table) {
            $table->id('tour_id');
            $table->string('name', 200);
            $table->string('image_url', 200);
            $table->text('description')->nullable();
            $table->integer('duration_days');
            $table->integer('duration_nights');
            $table->enum('transportation', ['Flight', 'Car']);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();

            // FK
            $table->unsignedBigInteger('depart_id'); 
            $table->foreign('depart_id')->references('location_id')->on('locations');
        });

        Schema::create('tour_destinations', function (Blueprint $table) {
            // Khóa chính phức hợp (Composite Primary Key)
            $table->primary(['tour_id', 'destination_id']);

            $table->unsignedBigInteger('tour_id');
            $table->unsignedBigInteger('destination_id');

            // Khóa ngoại
            $table->foreign('tour_id')->references('tour_id')->on('tours')->onDelete('cascade');
            $table->foreign('destination_id')->references('location_id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
