<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference_id')->unique();
            $table->string('environment', 32)->index();
            $table->string('exception_class');
            $table->text('message');
            $table->unsignedSmallInteger('status_code')->default(500)->index();
            $table->string('request_method', 10)->nullable();
            $table->text('request_path')->nullable();
            $table->string('route_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
