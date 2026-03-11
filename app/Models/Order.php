<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'listing_id',
        'total_price_usd',
        'order_status',
        'purchased_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_price_usd' => 'decimal:2',
            'purchased_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
