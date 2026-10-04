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
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->string('trim')->nullable();
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('price');
            $table->unsignedInteger('previous_price')->nullable();
            $table->unsignedInteger('mileage')->default(0);
            $table->string('body_type', 32);
            $table->string('condition', 32);
            $table->string('status', 32)->default('available');
            $table->string('fuel_type', 32);
            $table->string('transmission', 32);
            $table->string('drivetrain', 8);
            $table->string('engine')->nullable();
            $table->unsignedSmallInteger('horsepower')->nullable();
            $table->unsignedSmallInteger('torque')->nullable();
            $table->decimal('zero_to_sixty', 3, 1)->nullable();
            $table->unsignedSmallInteger('top_speed')->nullable();
            $table->string('exterior_color')->nullable();
            $table->string('interior_color')->nullable();
            $table->string('vin', 17)->nullable()->unique();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('price');
            $table->index('body_type');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'vehicle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('vehicles');
    }
};
