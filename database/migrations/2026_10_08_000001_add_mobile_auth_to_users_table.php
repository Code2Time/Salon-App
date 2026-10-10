<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 11)->nullable()->unique()->after('name');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');
            $table->string('system_role')->nullable()->index()->after('mobile_verified_at');
            $table->boolean('is_active')->default(true)->after('system_role');
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropIndex(['system_role']);
            $table->dropColumn(['mobile', 'mobile_verified_at', 'system_role', 'is_active']);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
