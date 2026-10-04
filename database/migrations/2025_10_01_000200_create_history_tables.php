<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ownership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 20);
            $table->string('title', 140);
            $table->text('description')->nullable();
            $table->date('performed_on');
            $table->unsignedInteger('mileage');
            $table->unsignedBigInteger('cost_cents')->nullable();
            $table->json('line_items')->nullable();
            $table->string('provider_type', 20);
            $table->string('provider_name', 120)->nullable();
            $table->string('provider_email')->nullable();
            $table->json('tasks')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'performed_on']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ownership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('name', 160);
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->date('expires_on')->nullable();
            $table->timestamp('expiry_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ownership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_record_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('reading');
            $table->date('recorded_on');
            $table->string('source', 20);
            $table->timestamps();

            $table->index(['vehicle_id', 'recorded_on']);
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('task', 80);
            $table->unsignedInteger('interval_miles')->nullable();
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->date('last_done_on')->nullable();
            $table->unsignedInteger('last_done_mileage')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ownership_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->unsignedBigInteger('amount_cents');
            $table->date('spent_on');
            $table->unsignedInteger('odometer')->nullable();
            $table->decimal('volume', 8, 3)->nullable();
            $table->string('notes', 200)->nullable();
            $table->timestamps();

            $table->index(['ownership_id', 'spent_on']);
        });

        Schema::create('recalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('campaign_number', 30);
            $table->string('component', 200);
            $table->text('summary');
            $table->text('consequence')->nullable();
            $table->text('remedy')->nullable();
            $table->date('reported_on')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'campaign_number']);
        });

        Schema::create('shop_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shop_name', 120);
            $table->string('shop_email');
            $table->string('status', 20)->default('pending');
            $table->string('responder_name', 120)->nullable();
            $table->text('response_note')->nullable();
            $table->string('responder_ip', 45)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_verifications');
        Schema::dropIfExists('recalls');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('odometer_readings');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('service_records');
    }
};
