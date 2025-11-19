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
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('blacklist_request_status')->nullable()->after('date_blacklisted'); // 'pending', 'approved', 'rejected'
            $table->text('blacklist_request_reason')->nullable()->after('blacklist_request_status');
            $table->foreignId('blacklist_requested_by')->nullable()->after('blacklist_request_reason')->constrained('users')->nullOnDelete();
            $table->dateTime('blacklist_requested_at')->nullable()->after('blacklist_requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropForeign(['blacklist_requested_by']);
            $table->dropColumn([
                'blacklist_request_status',
                'blacklist_request_reason',
                'blacklist_requested_by',
                'blacklist_requested_at',
            ]);
        });
    }
};
