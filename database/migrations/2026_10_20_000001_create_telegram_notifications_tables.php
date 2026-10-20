<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_user', function (Blueprint $table) {
            $table->string('telegram_chat_id', 30)->nullable();
        });
        Schema::create('telegram_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id', 30);
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('last_error_code')->nullable();
            $table->string('message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_deliveries');
        Schema::table('salon_user', fn (Blueprint $table) => $table->dropColumn('telegram_chat_id'));
    }
};
