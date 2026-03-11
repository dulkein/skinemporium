<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skins', function (Blueprint $table) {
            $table->string('market_category')->nullable()->after('skin_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('skins', function (Blueprint $table) {
            $table->dropIndex(['market_category']);
            $table->dropColumn('market_category');
        });
    }
};
