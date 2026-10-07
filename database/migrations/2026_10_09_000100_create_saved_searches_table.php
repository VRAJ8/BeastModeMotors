<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A buyer's marketplace search, kept so they hear about new cars that match it.
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('filters');
            $table->string('filters_hash', 64);
            $table->boolean('email_alerts')->default(true);
            // Listings published up to here have been considered for an alert.
            $table->timestamp('notified_through');
            $table->timestamps();

            $table->unique(['user_id', 'filters_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
