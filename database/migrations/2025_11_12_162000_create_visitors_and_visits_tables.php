<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Visitors
        if (! Schema::hasTable('visitors')) {
            Schema::create('visitors', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->nullable();
                $table->string('mobile')->nullable();
                $table->string('tag_number')->nullable();
                $table->string('image_url')->nullable();
                $table->timestamps();
                // Auditing
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // Reasons for visit (lookup)
        if (! Schema::hasTable('reasons_for_visit')) {
            Schema::create('reasons_for_visit', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
                // Auditing
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // Visits
        if (! Schema::hasTable('visits')) {
            Schema::create('visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
                $table->foreignId('staff_visited_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reason_for_visit_id')->nullable()->constrained('reasons_for_visit')->nullOnDelete();
                $table->dateTime('checkin_time');
                $table->dateTime('checkout_time')->nullable();
                $table->timestamps();
                // Auditing
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
        Schema::dropIfExists('reasons_for_visit');
        Schema::dropIfExists('visitors');
    }
};
