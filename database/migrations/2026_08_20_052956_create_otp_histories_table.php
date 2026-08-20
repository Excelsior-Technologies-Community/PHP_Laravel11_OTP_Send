<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_histories', function (Blueprint $table) {
            $table->id();

            $table->string('channel'); // email or sms

            $table->string('recipient');

            $table->string('action'); // send or verify

            $table->string('status'); // success, failed, blocked

            $table->ipAddress('ip_address')->nullable();

            $table->text('message')->nullable();

            $table->timestamps();

            $table->index(['channel', 'recipient']);
            $table->index(['action', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_histories');
    }
};