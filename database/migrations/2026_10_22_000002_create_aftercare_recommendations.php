<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salons', fn (Blueprint $table) => $table->unsignedSmallInteger('aftercare_discount_validity_days')->default(7));
        Schema::create('aftercare_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('care_instructions');
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->string('discount_code', 40)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->unique(['appointment_id', 'product_id']);
            $table->index(['customer_id', 'created_at']);
        });
        Schema::table('product_orders', function (Blueprint $table) {
            $table->foreignId('aftercare_recommendation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('subtotal_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->string('discount_code', 40)->nullable();
        });
        DB::table('product_orders')->update(['subtotal_amount' => DB::raw('total_amount')]);
        Schema::table('product_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('product_order_items', fn (Blueprint $table) => $table->dropColumn(['discount_amount', 'discount_percent']));
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropForeign(['aftercare_recommendation_id']);
            $table->dropUnique(['aftercare_recommendation_id']);
            $table->dropColumn(['aftercare_recommendation_id', 'subtotal_amount', 'discount_amount', 'discount_code']);
        });
        Schema::dropIfExists('aftercare_recommendations');
        Schema::table('salons', fn (Blueprint $table) => $table->dropColumn('aftercare_discount_validity_days'));
    }
};
