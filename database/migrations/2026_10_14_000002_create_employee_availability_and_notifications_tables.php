<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_availability_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_user_id')->constrained('salon_user')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_present')->default(false);
            $table->timestamps();
            $table->unique(['salon_user_id', 'date']);
        });

        Schema::create('employee_availability_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_availability_day_id');
            $table->foreign('employee_availability_day_id', 'employee_period_day_fk')
                ->references('id')->on('employee_availability_days')->cascadeOnDelete();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('employee_availability_periods');
        Schema::dropIfExists('employee_availability_days');
    }
};
