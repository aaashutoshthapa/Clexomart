<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToUser1Table extends Migration
{
    public function up(): void
    {
        Schema::table('USER1', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('otp_expires_at');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('USER1', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
}