<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmService extends Model
{
    protected $fillable = [
        'provider_id',
        'service_id',
        'name',
        'type',
        'category',
        'rate',
        'min',
        'max',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'rate' => 'decimal:4',
        'min' => 'integer',
        'max' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmmProvider::class, 'provider_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SmmOrder::class, 'service_id');
    }
}

