<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_salon_user', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salon_user_id')->constrained('salon_user')->cascadeOnDelete();
            $table->primary(['service_id', 'salon_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_salon_user');
    }
};
