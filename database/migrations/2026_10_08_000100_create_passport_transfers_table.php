<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A one-time link an owner gives the person they sold the car to outside the marketplace.
        Schema::create('passport_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Only a hash of the link's token is kept: the owner sees the link once, when it's made.
            $table->string('token_hash', 64)->unique();
            $table->unsignedInteger('sale_mileage');
            $table->string('odometer_status', 20);
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_transfers');
    }
};
