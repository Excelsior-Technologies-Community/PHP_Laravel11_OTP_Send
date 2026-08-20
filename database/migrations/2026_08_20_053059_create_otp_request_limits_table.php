<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_request_limits', function (Blueprint $table) {
            $table->id();

            $table->string('channel');

            $table->string('recipient');

            $table->ipAddress('ip_address')->nullable();

            $table->unsignedInteger('request_count')->default(0);

            $table->timestamp('window_started_at');

            $table->timestamps();

            $table->unique(
                ['channel', 'recipient'],
                'otp_request_limits_channel_recipient_unique'
            );

            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_request_limits');
    }
};