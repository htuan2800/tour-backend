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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('booking_id', 50)->unique();
            $table->string('transaction_id', 100)->nullable();
            $table->string('transaction_code', 100)->nullable();
            $table->string('response_code')->nullable();
            $table->string('bank_code')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_status', ['PENDING', 'COMPLETED', 'FAILED', 'REFUNDED', 'EXPIRED'])->default('PENDING');
            $table->enum('payment_method', ['VNPAY', 'MOMO', 'CASH']);
            $table->string('payment_details')->nullable();
            $table->timestamp('payment_date')->useCurrent();

            $table->foreign('booking_id')->references('booking_id')->on('bookings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
