<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A shop is first named by whichever owner asked it to verify; the shop confirms or corrects that name
        // the first time it answers (or when it completes its profile).
        Schema::table('shops', function (Blueprint $table) {
            $table->timestamp('name_confirmed_at')->nullable()->after('name');
        });

        DB::table('shops')->whereNotNull('profile_completed_at')->update(['name_confirmed_at' => DB::raw('profile_completed_at')]);
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('name_confirmed_at');
        });
    }
};
