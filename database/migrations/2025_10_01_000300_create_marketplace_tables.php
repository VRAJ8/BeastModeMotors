<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 40)->unique();
            $table->string('label', 80);
            $table->boolean('show_costs')->default(false);
            $table->boolean('show_full_vin')->default(false);
            $table->boolean('show_documents')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('share_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('price_cents');
            $table->unsignedInteger('mileage');
            $table->string('city', 80);
            $table->string('state', 2);
            $table->string('zip', 10)->nullable();
            $table->text('description');
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->string('removed_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('saved_listings', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'listing_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 20);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('agreed_price_cents')->nullable();
            $table->unsignedInteger('sale_mileage')->nullable();
            $table->json('handover')->nullable();
            $table->timestamp('agreed_at')->nullable();
            $table->timestamp('buyer_confirmed_at')->nullable();
            $table->timestamp('seller_confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });

        Schema::create('deal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->json('risk_flags')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('note', 300)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_for')->nullable();
            $table->string('location', 160)->nullable();
            $table->string('inspector', 120)->nullable();
            $table->json('results')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('deal_messages');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('saved_listings');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('share_links');
    }
};
