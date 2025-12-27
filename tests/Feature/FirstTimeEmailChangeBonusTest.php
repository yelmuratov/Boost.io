<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('first time user with email typo gets bonus after email change and verification', function () {
    Notification::fake();

    // 1. Register with typo email (never verify)
    $user = User::factory()->create([
        'email' => 'typo@gmial.com',
        'email_verified_at' => null,  // NEVER VERIFIED!
        'bonus_balance' => 0,
    ]);

    // Verify no bonus transaction exists
    expect($user->transactions()->where('type', 'bonus_award')->exists())
        ->toBeFalse('User should have no bonus transactions yet');

    // 2. User changes email to correct one
    $response = $this->actingAs($user)->postJson('/api/email/change-request', [
        'new_email' => 'correct@gmail.com',
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->pending_email)->toBe('correct@gmail.com');

    // 3. Get the token from database (simulating clicking email link)
    $token = \Str::random(64);
    $user->update([
        'pending_email_token' => hash('sha256', $token),
    ]);

    // 4. Verify email change
    $verifyResponse = $this->get("/api/email/verify-change/{$user->id}/{$token}");
    $verifyResponse->assertStatus(200);

    $user->refresh();

    // Email should be changed
    expect($user->email)->toBe('correct@gmail.com');
    expect($user->email_verified_at)->toBeNull('Email should need re-verification');
    expect($user->pending_email)->toBeNull('Pending email should be cleared');

    // Still no bonus (not verified yet)
    expect($user->bonus_balance)->toBe(0.0);
    expect($user->transactions()->where('type', 'bonus_award')->exists())
        ->toBeFalse('Still no bonus before first verification');

    // 5. Now verify the NEW email for the FIRST TIME
    $user->markEmailAsVerified();
    event(new \Illuminate\Auth\Events\Verified($user));

    $user->refresh();

    // ✅ NOW should get bonus (first verification ever!)
    expect($user->email_verified_at)->not->toBeNull('Email should be verified');
    expect($user->bonus_balance)->toBe(5000.0, 'User should receive welcome bonus on first verification');
    expect($user->transactions()->where('type', 'bonus_award')->exists())
        ->toBeTrue('Bonus transaction should be created');
});

test('user who already got bonus does not get it again after email change', function () {
    // User who already received and spent bonus
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now(),
        'bonus_balance' => 0,  // Spent all bonus
    ]);

    // Create bonus transaction (user already got bonus before)
    $user->transactions()->create([
        'type' => 'bonus_award',
        'amount' => 5000,
        'balance_before' => 0,
        'balance_after' => 5000,
        'description' => 'Welcome bonus',
        'metadata' => [],
    ]);

    // Change email
    $token = \Str::random(64);
    $user->update([
        'pending_email' => 'newemail@example.com',
        'pending_email_token' => hash('sha256', $token),
        'email_change_requested_at' => now(),
    ]);

    // Verify email change
    $this->get("/api/email/verify-change/{$user->id}/{$token}");

    $user->refresh();
    expect($user->email)->toBe('newemail@example.com');

    // Verify new email
    $user->markEmailAsVerified();
    event(new \Illuminate\Auth\Events\Verified($user));

    $user->refresh();

    // Should NOT get bonus again
    expect($user->bonus_balance)->toBe(0.0, 'Should not receive bonus again');

    // Should still have only ONE bonus transaction
    expect($user->transactions()->where('type', 'bonus_award')->count())
        ->toBe(1, 'Should have only one bonus transaction');
});
