<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that point at a user who may delete their account. The other party's deal, listing history and
     * offers must survive that, and so must a car's history: a passport with earlier owners in it stays, unowned.
     */
    private const USER_LINKS = [
        'vehicles' => ['user_id'],
        'listings' => ['seller_id'],
        'deals' => ['buyer_id', 'seller_id'],
        'offers' => ['user_id'],
    ];

    /**
     * Foreign keys that lists and joins filter on. Postgres doesn't index them by itself.
     */
    private const INDEXES = [
        'vehicles' => ['user_id'],
        'vehicle_photos' => ['vehicle_id'],
        'ownerships' => ['user_id'],
        'service_records' => ['ownership_id', 'shop_id'],
        'documents' => ['vehicle_id', 'ownership_id', 'service_record_id'],
        'odometer_readings' => ['ownership_id', 'service_record_id'],
        'reminders' => ['vehicle_id'],
        'expenses' => ['vehicle_id'],
        'recalls' => ['service_record_id'],
        'shop_verifications' => ['service_record_id', 'shop_id', 'requested_by'],
        'share_links' => ['vehicle_id'],
        'listings' => ['vehicle_id', 'seller_id'],
        'saved_listings' => ['listing_id'],
        'reports' => ['listing_id'],
        'deals' => ['listing_id', 'vehicle_id'],
        'deal_messages' => ['deal_id'],
        'offers' => ['deal_id', 'user_id'],
    ];

    public function up(): void
    {
        foreach (self::USER_LINKS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->unsignedBigInteger($column)->nullable()->change();
                    $blueprint->foreign($column)->references('id')->on('users')->nullOnDelete();
                }
            });
        }

        foreach (self::INDEXES as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasIndex($table, [$column])) {
                        $blueprint->index($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropIndex([$column]);
                }
            });
        }

        foreach (self::USER_LINKS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropForeign([$column]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->unsignedBigInteger($column)->nullable(false)->change();
                    $blueprint->foreign($column)->references('id')->on('users')->cascadeOnDelete();
                }
            });
        }
    }
};
