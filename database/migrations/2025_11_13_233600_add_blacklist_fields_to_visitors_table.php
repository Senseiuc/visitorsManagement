<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('visitors')) {
            Schema::table('visitors', function (Blueprint $table) {
                if (! Schema::hasColumn('visitors', 'is_blacklisted')) {
                    $table->boolean('is_blacklisted')->default(false)->after('image_url');
                }
                if (! Schema::hasColumn('visitors', 'reasons_for_blacklisting')) {
                    $table->text('reasons_for_blacklisting')->nullable()->after('is_blacklisted');
                }
                if (! Schema::hasColumn('visitors', 'date_blacklisted')) {
                    $table->dateTime('date_blacklisted')->nullable()->after('reasons_for_blacklisting');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visitors')) {
            Schema::table('visitors', function (Blueprint $table) {
                if (Schema::hasColumn('visitors', 'date_blacklisted')) {
                    $table->dropColumn('date_blacklisted');
                }
                if (Schema::hasColumn('visitors', 'reasons_for_blacklisting')) {
                    $table->dropColumn('reasons_for_blacklisting');
                }
                if (Schema::hasColumn('visitors', 'is_blacklisted')) {
                    $table->dropColumn('is_blacklisted');
                }
            });
        }
    }
};
