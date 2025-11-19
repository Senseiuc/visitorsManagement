<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['superadmin', 'admin', 'receptionist'])->default('receptionist')->after('password');
            }

            if (! Schema::hasColumn('users', 'assigned_location_id')) {
                $table->foreignId('assigned_location_id')->nullable()->after('role')->constrained('locations')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('assigned_location_id');
            }

            if (! Schema::hasColumn('users', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('permissions')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
            if (Schema::hasColumn('users', 'assigned_location_id')) {
                $table->dropConstrainedForeignId('assigned_location_id');
            }
            if (Schema::hasColumn('users', 'permissions')) {
                $table->dropColumn('permissions');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
