<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can request email change', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/email/change-request', [
        'new_email' => 'new@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $user->refresh();
    expect($user->pending_email)->toBe('new@example.com');
    expect($user->pending_email_token)->not->toBeNull();
    expect($user->email_change_requested_at)->not->toBeNull();

    Notification::assertSentOnDemand(\App\Notifications\EmailChangeVerificationNotification::class);
});

test('email change validates unique email', function () {
    $existingUser = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->actingAs($user)->postJson('/api/email/change-request', [
        'new_email' => 'taken@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.new_email.0', 'This email address is already in use.');
});

test('email change rate limiting prevents spam', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'email_change_requested_at' => now()->subHours(7), // Within 8 hour window
    ]);

    $response = $this->actingAs($user)->postJson('/api/email/change-request', [
        'new_email' => 'new@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You can only request email changes 3 times per day. Please try again later.');
});

test('user can verify email change', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
        'pending_email' => 'new@example.com',
        'pending_email_token' => hash('sha256', 'test-token'),
        'email_change_requested_at' => now(),
    ]);

    $response = $this->get("/api/email/verify-change/{$user->id}/test-token");

    $response->assertStatus(200);
    $response->assertSee('Email Updated!');

    $user->refresh();
    expect($user->email)->toBe('new@example.com');
    expect($user->pending_email)->toBeNull();
    expect($user->pending_email_token)->toBeNull();
    expect($user->email_verified_at)->toBeNull(); // Should be reset for re-verification
});

test('email change verification expires after 60 minutes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'pending_email' => 'new@example.com',
        'pending_email_token' => hash('sha256', 'test-token'),
        'email_change_requested_at' => now()->subMinutes(61), // Expired
    ]);

    $response = $this->get("/api/email/verify-change/{$user->id}/test-token");

    $response->assertStatus(200);
    $response->assertSee('Verification Failed');
    $response->assertSee('expired');
});

test('user can cancel email change', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'pending_email' => 'new@example.com',
        'pending_email_token' => hash('sha256', 'token'),
        'email_change_requested_at' => now(),
    ]);

    $response = $this->actingAs($user)->postJson('/api/email/cancel-change');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $user->refresh();
    expect($user->pending_email)->toBeNull();
    expect($user->pending_email_token)->toBeNull();
    expect($user->email_change_requested_at)->toBeNull();
});

test('bonus not awarded on email re-verification after change', function () {
    // Create user with verified email and existing bonus
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now(),
        'bonus_balance' => 5000, // Already has bonus from first verification
    ]);

    // Request email change
    $token = \Str::random(64);
    $user->update([
        'pending_email' => 'newemail@example.com',
        'pending_email_token' => hash('sha256', $token),
        'email_change_requested_at' => now(),
    ]);

    // Verify email change
    $this->get("/api/email/verify-change/{$user->id}/{$token}");

    $user->refresh();

    // Email should be updated
    expect($user->email)->toBe('newemail@example.com');
    expect($user->email_verified_at)->toBeNull(); // Reset for re-verification

    // Now verify the new email
    $user->markEmailAsVerified();
    event(new \Illuminate\Auth\Events\Verified($user));

    // Wait for event to process
    $user->refresh();

    // Bonus should remain the same (not doubled)
    expect($user->bonus_balance)->toBe(5000.0);
});
