<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_interval_minutes')->default(15);
            $table->unsignedSmallInteger('cancellation_notice_hours')->default(5);
            $table->boolean('deposit_enabled')->default(false);
            $table->string('deposit_type', 16)->nullable();
            $table->unsignedBigInteger('deposit_value')->nullable();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 16)->default('customer');
            $table->string('status', 24)->default('confirmed');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedBigInteger('total_price');
            $table->unsignedBigInteger('required_deposit_amount')->default(0);
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['salon_id', 'starts_at', 'status']);
            $table->index(['customer_id', 'starts_at']);
        });

        Schema::create('appointment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salon_user_id')->constrained('salon_user')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('service_name', 150);
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('duration_minutes');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->unique(['appointment_id', 'position']);
        });

        Schema::create('appointment_reservation_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_user_id')->constrained('salon_user')->cascadeOnDelete();
            $table->dateTime('slot_at');
            $table->unique(['salon_user_id', 'slot_at'], 'employee_minute_slot_unique');
            $table->index(['appointment_id', 'salon_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reservation_slots');
        Schema::dropIfExists('appointment_items');
        Schema::dropIfExists('appointments');
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->dropColumn(['slot_interval_minutes', 'cancellation_notice_hours', 'deposit_enabled', 'deposit_type', 'deposit_value']);
        });
    }
};
