<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['salon_id', 'is_active']);
        });
        Schema::create('product_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->text('recommendation_text')->nullable();
            $table->boolean('recommendation_enabled')->default(true);
            $table->boolean('discount_enabled')->default(false);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->unique(['product_id', 'service_id']);
        });
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->uuid('request_id');
            $table->string('request_hash', 64);
            $table->string('status')->default('registered');
            $table->string('payment_status')->default('unpaid');
            $table->unsignedBigInteger('total_amount');
            $table->string('customer_name');
            $table->string('customer_mobile', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'request_id']);
            $table->index(['salon_id', 'created_at']);
        });
        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 150);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total_amount');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_items');
        Schema::dropIfExists('product_orders');
        Schema::dropIfExists('product_service');
        Schema::dropIfExists('products');
    }
};
