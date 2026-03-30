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
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('booking_id', 50)->primary();
            $table->unsignedBigInteger('user_id')->nullable(); // Người đặt
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('contact_fullName');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->string('contact_address');
            
            $table->timestamp('booking_date')->useCurrent();
            $table->decimal('applied_price_adult', 15, 2);
            $table->decimal('applied_price_children', 15, 2);
            $table->integer('number_of_adults')->default(1);
            $table->integer('number_of_children')->default(0);
            
            // Cột tiền
            $table->decimal('original_price', 15, 2); 
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2);

            // Thanh toán
            $table->enum('status', ['PENDING', 'VERIFYING', 'PAID', 'EXPIRED', 'CANCELLED'])->default('PENDING');
            $table->text('note')->nullable();

            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('schedule_id')->references('schedule_id')->on('tour_schedules');
            $table->foreign('coupon_id')->references('coupon_id')->on('coupons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
