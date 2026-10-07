<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('city', 80)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('website')->nullable();
            $table->text('about')->nullable();
            $table->json('specialties')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('shop_verifications', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('requested_by')->constrained()->nullOnDelete();
        });

        Schema::table('service_records', function (Blueprint $table) {
            $table->foreignId('shop_id')->nullable()->after('provider_email')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('shop_id'));
        Schema::table('shop_verifications', fn (Blueprint $table) => $table->dropConstrainedForeignId('shop_id'));
        Schema::dropIfExists('shops');
    }
};
