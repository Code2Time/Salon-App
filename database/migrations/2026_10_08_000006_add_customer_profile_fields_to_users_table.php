<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 50)->nullable()->after('name');
            $table->string('last_name', 70)->nullable()->after('first_name');
            $table->date('birth_date')->nullable()->after('last_name');
        });

        DB::table('users')->orderBy('id')->eachById(function ($user) {
            $parts = preg_split('/\s+/u', trim((string) $user->name), 2) ?: [];
            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?? null,
                'last_name' => $parts[1] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'birth_date']);
        });
    }
};
