<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff vetting lets a shop into the directory before several owners have vouched for it.
        Schema::table('shops', function (Blueprint $table) {
            $table->timestamp('vetted_at')->nullable()->after('is_listed');
        });

        // A reminder remembers which record last completed it, so editing or deleting that record rolls it back,
        // and keeps what the owner typed in by hand as the fallback.
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('last_done_record_id')->nullable()->after('last_done_mileage')->constrained('service_records')->nullOnDelete();
            $table->date('baseline_done_on')->nullable()->after('last_done_record_id');
            $table->unsignedInteger('baseline_done_mileage')->nullable()->after('baseline_done_on');
        });

        DB::table('reminders')->update([
            'baseline_done_on' => DB::raw('last_done_on'),
            'baseline_done_mileage' => DB::raw('last_done_mileage'),
        ]);
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_done_record_id');
            $table->dropColumn(['baseline_done_on', 'baseline_done_mileage']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('vetted_at');
        });
    }
};
