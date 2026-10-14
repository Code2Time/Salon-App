<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('planning_period', 16)->default('weekly');
            $table->boolean('is_recurring')->default(false);
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->timestamps();
        });

        Schema::create('salon_working_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_open')->default(false);
            $table->string('title', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['salon_id', 'date']);
            $table->index(['salon_id', 'is_open', 'date']);
        });

        Schema::create('salon_working_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_working_day_id')->constrained()->cascadeOnDelete();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['salon_working_day_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_working_periods');
        Schema::dropIfExists('salon_working_days');
        Schema::dropIfExists('appointment_settings');
    }
};
