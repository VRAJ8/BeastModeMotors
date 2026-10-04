<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vin', 17)->unique();
            $table->boolean('vin_valid')->default(false);
            $table->string('decode_source', 16)->nullable();
            $table->json('decoded')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('make', 60);
            $table->string('model', 80);
            $table->string('trim', 120)->nullable();
            $table->string('body', 60)->nullable();
            $table->string('engine', 120)->nullable();
            $table->string('drivetrain', 40)->nullable();
            $table->string('transmission', 60)->nullable();
            $table->string('fuel_type', 20)->default('gasoline');
            $table->string('exterior_color', 40)->nullable();
            $table->string('nickname', 60)->nullable();
            $table->unsignedInteger('current_mileage')->default(0);
            $table->timestamp('recalls_checked_at')->nullable();
            $table->timestamps();

            $table->index(['make', 'model']);
        });

        Schema::create('vehicle_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption', 120)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('owner_number');
            $table->string('acquired_via', 20);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->unsignedInteger('start_mileage')->default(0);
            $table->unsignedInteger('end_mileage')->nullable();
            $table->unsignedBigInteger('purchase_price_cents')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'owner_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownerships');
        Schema::dropIfExists('vehicle_photos');
        Schema::dropIfExists('vehicles');
    }
};
