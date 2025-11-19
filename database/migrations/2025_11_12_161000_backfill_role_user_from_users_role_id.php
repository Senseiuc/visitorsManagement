<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('role_user')) {
            return; // pivot not present
        }

        // Copy existing users.role_id values into role_user pivot
        $rows = DB::table('users')->whereNotNull('role_id')->get(['id', 'role_id']);
        foreach ($rows as $row) {
            $exists = DB::table('role_user')
                ->where('user_id', $row->id)
                ->where('role_id', $row->role_id)
                ->exists();
            if (! $exists) {
                DB::table('role_user')->insert([
                    'user_id' => $row->id,
                    'role_id' => $row->role_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No-op: leave pivot rows intact on rollback
    }
};
