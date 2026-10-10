<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertToLocalFormat('users');
        $this->convertToLocalFormat('otp_challenges');

        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 11)->nullable()->change();
        });
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->string('mobile', 11)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 12)->nullable()->change();
        });
        Schema::table('otp_challenges', function (Blueprint $table) {
            $table->string('mobile', 12)->change();
        });

        $this->convertToInternationalFormat('users');
        $this->convertToInternationalFormat('otp_challenges');
    }

    private function convertToLocalFormat(string $table): void
    {
        DB::table($table)
            ->where('mobile', 'like', '98%')
            ->orderBy('id')
            ->eachById(function ($record) use ($table) {
                DB::table($table)->where('id', $record->id)->update([
                    'mobile' => '0'.substr($record->mobile, 2),
                ]);
            });
    }

    private function convertToInternationalFormat(string $table): void
    {
        DB::table($table)
            ->where('mobile', 'like', '09%')
            ->orderBy('id')
            ->eachById(function ($record) use ($table) {
                DB::table($table)->where('id', $record->id)->update([
                    'mobile' => '98'.substr($record->mobile, 1),
                ]);
            });
    }
};
