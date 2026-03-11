<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'source',
        'seller_id',
        'skin_id',
        'condition',
        'float_value',
        'price_usd',
        'inspect_link',
        'listing_url',
        'status',
        'listed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'float_value' => 'decimal:5',
            'price_usd' => 'decimal:2',
            'listed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function skin(): BelongsTo
    {
        return $this->belongsTo(Skin::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'watchlists')->withTimestamps();
    }
}
