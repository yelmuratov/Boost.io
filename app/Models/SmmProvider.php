<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmProvider extends Model
{
    protected $fillable = [
        'name',
        'api_url',
        'api_key',
        'is_active',
        'verification_status',
        'priority',
        'balance',
        'last_sync_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'balance' => 'decimal:2',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_key', // Hide API key from JSON responses
    ];

    protected $appends = [
        'masked_api_key', // Show masked version
    ];

    public function services(): HasMany
    {
        return $this->hasMany(SmmService::class, 'provider_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SmmOrder::class, 'provider_id');
    }

    public function getMaskedApiKeyAttribute(): string
    {
        if (empty($this->api_key)) {
            return '';
        }
        return substr($this->api_key, 0, 4) . '****' . substr($this->api_key, -4);
    }
}

