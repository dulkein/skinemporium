<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skins', function (Blueprint $table) {
            $table->id();
            $table->string('market_hash_name')->unique();
            $table->string('weapon_name')->nullable();
            $table->string('skin_name')->nullable();
            $table->string('rarity')->nullable();
            $table->string('image_url')->nullable();
            $table->string('external_item_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skins');
    }
};
