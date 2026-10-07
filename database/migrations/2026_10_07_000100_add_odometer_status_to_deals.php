<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The seller's federal odometer certification, given with the handover reading.
        Schema::table('deals', function (Blueprint $table) {
            $table->string('odometer_status', 20)->nullable()->after('sale_mileage');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('odometer_status');
        });
    }
};
