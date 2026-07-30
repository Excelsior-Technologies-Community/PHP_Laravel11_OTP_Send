<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->integer('attempts')->default(0)->after('used');
            $table->integer('max_attempts')->default(5)->after('attempts');
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'max_attempts']);
        });
    }
};