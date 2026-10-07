<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The asking price before the latest cut on a live listing, shown as "was $X".
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedBigInteger('previous_price_cents')->nullable()->after('price_cents');
            $table->timestamp('price_dropped_at')->nullable()->after('previous_price_cents');
        });

        // The lowest price each buyer has been told about for a car they saved: only a cut below it is news.
        Schema::table('saved_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('notified_price_cents')->nullable();
        });

        DB::table('saved_listings')->update([
            'notified_price_cents' => DB::table('listings')->select('price_cents')->whereColumn('listings.id', 'saved_listings.listing_id'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('price_drop_alerts')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('price_drop_alerts'));
        Schema::table('saved_listings', fn (Blueprint $table) => $table->dropColumn('notified_price_cents'));
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['previous_price_cents', 'price_dropped_at']));
    }
};
