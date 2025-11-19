<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Locations
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('address')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('locations', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
        });

        // Floors
        Schema::table('floors', function (Blueprint $table) {
            if (! Schema::hasColumn('floors', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('name')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('floors', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
        });

        // Departments
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('name')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('departments', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
        });

        // Roles
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'created_by_user_id')) {
                    $table->foreignId('created_by_user_id')->nullable()->after('permissions')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('roles', 'updated_by_user_id')) {
                    $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
                }
            });
        }

        // Users (already has created_by_user_id; add updated_by_user_id)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'updated_by_user_id')) {
                $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Reverse in safe order dropping FKs
        // Locations
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
            if (Schema::hasColumn('locations', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
        });

        // Floors
        Schema::table('floors', function (Blueprint $table) {
            if (Schema::hasColumn('floors', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
            if (Schema::hasColumn('floors', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
        });

        // Departments
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
            if (Schema::hasColumn('departments', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }
        });

        // Roles
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (Schema::hasColumn('roles', 'updated_by_user_id')) {
                    $table->dropConstrainedForeignId('updated_by_user_id');
                }
                if (Schema::hasColumn('roles', 'created_by_user_id')) {
                    $table->dropConstrainedForeignId('created_by_user_id');
                }
            });
        }

        // Users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'updated_by_user_id')) {
                $table->dropConstrainedForeignId('updated_by_user_id');
            }
        });
    }
};
