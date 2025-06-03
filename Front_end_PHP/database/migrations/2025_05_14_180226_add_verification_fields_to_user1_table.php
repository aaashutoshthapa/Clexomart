<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('USER1', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('password');
            $table->boolean('is_verified')->default(false)->after('otp');
            $table->timestamp('otp_expires_at')->nullable()->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('USER1', function (Blueprint $table) {
            $table->dropColumn(['otp', 'is_verified', 'otp_expires_at']);
        });
    }
};