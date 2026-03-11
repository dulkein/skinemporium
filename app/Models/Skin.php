<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skin extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_hash_name',
        'weapon_name',
        'skin_name',
        'market_category',
        'rarity',
        'image_url',
        'external_item_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
