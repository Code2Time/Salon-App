<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salon_user')->where('role', 'manager')->update(['role' => 'salon_owner']);
    }

    public function down(): void
    {
        // تبدیل معکوس امن نیست؛ مالک اولیه و مدیر تبدیل‌شده قابل تفکیک نیستند.
    }
};
