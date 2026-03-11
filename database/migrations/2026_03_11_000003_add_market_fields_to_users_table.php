<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('steam_id', 32)->nullable()->unique()->after('email');
            $table->string('steam_trade_url')->nullable()->after('steam_id');
            $table->string('avatar_url')->nullable()->after('steam_trade_url');
            $table->decimal('wallet_balance', 10, 2)->default(0)->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['steam_id']);
            $table->dropColumn(['steam_id', 'steam_trade_url', 'avatar_url', 'wallet_balance']);
        });
    }
};
