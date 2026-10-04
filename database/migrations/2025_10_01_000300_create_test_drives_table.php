<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_drives', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->dateTime('scheduled_at');
            $table->string('status', 32)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_drives');
    }
};
