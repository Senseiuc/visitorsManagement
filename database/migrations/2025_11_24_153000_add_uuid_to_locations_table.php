<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Populate existing records
        \Illuminate\Support\Facades\DB::table('locations')->get()->each(function ($location) {
            \Illuminate\Support\Facades\DB::table('locations')
                ->where('id', $location->id)
                ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
        });

        // Make it non-nullable after population
        Schema::table('locations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
