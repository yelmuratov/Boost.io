<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmmOrder extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'service_id',
        'order_id',
        'link',
        'quantity',
        'charge',
        'cost',
        'start_count',
        'remains',
        'status',
        'order_data',
        'response_data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'provider_id' => 'integer',
        'service_id' => 'integer',
        'quantity' => 'integer',
        'charge' => 'decimal:2',
        'cost' => 'decimal:2',
        'start_count' => 'integer',
        'remains' => 'integer',
        'order_data' => 'array',
        'response_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmmProvider::class, 'provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SmmService::class, 'service_id');
    }
}


