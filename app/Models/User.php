<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'email',
        'telegram_username',
        'password',
        'role',
        'balance',
        'bonus_balance',
        'total_spent',
        'bonus_unlocked',
        'bonus_unlocked_at',
        'bonus_awarded',
        'pending_email',
        'pending_email_token',
        'email_change_requested_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pending_email_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'bonus_balance' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'bonus_unlocked' => 'boolean',
            'bonus_unlocked_at' => 'datetime',
            'bonus_awarded' => 'boolean',
            'email_change_requested_at' => 'datetime',
        ];
    }

    public function RefreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SmmOrder::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Check if user can afford a given amount
     */
    public function canAfford(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get total available balance (main + unlocked bonus)
     */
    public function getTotalBalance(): float
    {
        $total = $this->balance;

        if ($this->bonus_unlocked && $this->bonus_balance > 0) {
            $total += $this->bonus_balance;
        }

        return $total;
    }
}

