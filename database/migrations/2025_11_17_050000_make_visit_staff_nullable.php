<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('visits')) {
            return;
        }

        // Make staff_visited_id nullable without requiring doctrine/dbal
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");
        if ($driver === 'mysql') {
            // Assuming standard big integer unsigned for foreignId
            DB::statement('ALTER TABLE `visits` MODIFY `staff_visited_id` BIGINT UNSIGNED NULL');
        } else {
            // Fallback for other drivers - attempt schema change
            Schema::table('visits', function (Blueprint $table) {
                $table->foreignId('staff_visited_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('visits')) {
            return;
        }

        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `visits` MODIFY `staff_visited_id` BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('visits', function (Blueprint $table) {
                $table->foreignId('staff_visited_id')->nullable(false)->change();
            });
        }
    }
};
