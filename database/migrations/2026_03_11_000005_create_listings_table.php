<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('source')->default('internal');
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('skin_id')->constrained('skins')->cascadeOnDelete();
            $table->string('condition')->nullable();
            $table->decimal('float_value', 6, 5)->nullable();
            $table->decimal('price_usd', 10, 2);
            $table->string('inspect_link')->nullable();
            $table->string('listing_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('listed_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
