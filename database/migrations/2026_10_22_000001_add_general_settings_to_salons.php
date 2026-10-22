<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('telegram_url', 500)->nullable();
            $table->string('whatsapp_url', 500)->nullable();
            $table->string('website_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('salons', fn (Blueprint $table) => $table->dropColumn([
            'logo', 'description', 'phone', 'address', 'instagram_url', 'telegram_url', 'whatsapp_url', 'website_url',
        ]));
    }
};
