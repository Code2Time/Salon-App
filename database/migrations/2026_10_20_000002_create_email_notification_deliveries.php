<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_user', function (Blueprint $table) {
            $table->string('notification_email')->nullable();
        });
        Schema::create('email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient');
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error_code', 50)->nullable();
            $table->string('message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
        Schema::table('salon_user', fn (Blueprint $table) => $table->dropColumn('notification_email'));
    }
};
