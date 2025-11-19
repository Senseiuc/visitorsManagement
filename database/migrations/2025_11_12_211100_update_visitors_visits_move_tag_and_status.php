<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Remove tag_number from visitors table
        if (Schema::hasTable('visitors')) {
            Schema::table('visitors', function (Blueprint $table) {
                if (Schema::hasColumn('visitors', 'tag_number')) {
                    $table->dropColumn('tag_number');
                }
            });
        }

        // Update visits table: add tag_number, status, and make checkin_time nullable
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table) {
                if (! Schema::hasColumn('visits', 'tag_number')) {
                    $table->string('tag_number', 100)->nullable()->after('reason_for_visit_id');
                }
                if (! Schema::hasColumn('visits', 'status')) {
                    $table->enum('status', ['pending', 'approved'])->default('pending')->after('checkout_time');
                }
            });

            // Alter checkin_time to be nullable
            if (Schema::hasColumn('visits', 'checkin_time')) {
                // Use raw SQL to avoid requiring doctrine/dbal for column modification
                $connection = config('database.default');
                $driver = config("database.connections.$connection.driver");
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE `visits` MODIFY `checkin_time` DATETIME NULL');
                } else {
                    // Fallback to schema change for other drivers
                    Schema::table('visits', function (Blueprint $table) {
                        $table->dateTime('checkin_time')->nullable()->change();
                    });
                }
            }
        }
    }

    public function down(): void
    {
        // Revert visits table changes
        if (Schema::hasTable('visits')) {
            // Make checkin_time nullable
            if (Schema::hasColumn('visits', 'checkin_time')) {
                $connection = config('database.default');
                $driver = config("database.connections.$connection.driver");
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE `visits` MODIFY `checkin_time` DATETIME NULL');
                } else {
                    Schema::table('visits', function (Blueprint $table) {
                        $table->dateTime('checkin_time')->nullable(false)->change();
                    });
                }
            }

            Schema::table('visits', function (Blueprint $table) {
                if (Schema::hasColumn('visits', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('visits', 'tag_number')) {
                    $table->dropColumn('tag_number');
                }
            });
        }

        // Add tag_number back to visitors
        if (Schema::hasTable('visitors')) {
            Schema::table('visitors', function (Blueprint $table) {
                if (! Schema::hasColumn('visitors', 'tag_number')) {
                    $table->string('tag_number')->nullable()->after('mobile');
                }
            });
        }
    }
};
