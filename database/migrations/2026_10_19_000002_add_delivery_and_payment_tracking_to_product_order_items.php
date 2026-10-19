<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_order_items', function (Blueprint $table) {
            $table->string('delivery_status')->default('pending')->after('total_amount');
            $table->timestamp('delivered_at')->nullable()->after('delivery_status');
            $table->string('payment_status')->default('unpaid')->after('delivered_at');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->unsignedBigInteger('amount_received')->default(0)->after('payment_method');
            $table->string('receipt_path')->nullable()->after('amount_received');
        });
    }

    public function down(): void
    {
        Schema::table('product_order_items', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'delivered_at', 'payment_status', 'payment_method', 'amount_received', 'receipt_path']);
        });
    }
};
