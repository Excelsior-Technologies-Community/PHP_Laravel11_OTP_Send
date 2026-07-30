<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->integer('login_otp_attempts')->default(0)->after('login_otp_expires_at');
            $table->timestamp('login_otp_locked_until')->nullable()->after('login_otp_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['login_otp_attempts', 'login_otp_locked_until']);
        });
    }
};