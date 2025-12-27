<?php
namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\SmmPanel\SmmService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{

    public function __construct(
    ) {
    }
    public function register(array $data): array
    {
        $user = User::create([
            'user_name' => $data['user_name'],
            'email' => $data['email'],
            'telegram_username' => $data['telegram_username'] ?? 'null',
            'password' => Hash::make($data['password']),
        ]);

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        $accessToken = $user->createToken('authToken')->plainTextToken;
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => '60 minutes'
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('user_name', $credentials['user_name'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'message' => 'The provided credentials are incorrect.',
            ]);
        }

        $user->tokens()->delete();
        $user->RefreshTokens()->where('expires_at', '<', now())->delete();

        $accessToken = $user->createToken('authToken')->plainTextToken;
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => '60 minutes'
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        $token = RefreshToken::where('token', hash('sha256', $refreshToken))->first();
        if (!$token || $token->isExpired()) {
            throw ValidationException::withMessages([
                'message' => 'The provided refresh token is invalid.',
            ]);
        }

        $user = $token->user;
        $user->tokens()->delete();

        $accessToken = $user->createToken('authToken')->plainTextToken;

        $token->delete();
        $newRefreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => '60 minutes'
        ];
    }

    /**
     * Request email change
     * Rate limit: 3 requests per 24 hours per user
     */
    public function requestEmailChange(User $user, string $newEmail): void
    {
        // Check if new email is already in use
        if (User::where('email', $newEmail)->exists()) {
            throw ValidationException::withMessages([
                'new_email' => 'This email address is already in use.',
            ]);
        }

        // Check rate limiting - 3 email change requests per 24 hours
        if ($user->email_change_requested_at && $user->email_change_requested_at->diffInHours(now()) < 8) {
            throw ValidationException::withMessages([
                'message' => 'You can only request email changes 3 times per day. Please try again later.',
            ]);
        }

        // Generate verification token
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        // Update user with pending email info
        $user->update([
            'pending_email' => $newEmail,
            'pending_email_token' => $hashedToken,
            'email_change_requested_at' => now(),
        ]);

        // Generate verification URL
        $verificationUrl = url("/api/email/verify-change/{$user->id}/{$token}");

        // Send verification email to new email address
        \Notification::route('mail', $newEmail)->notify(
            new \App\Notifications\EmailChangeVerificationNotification($verificationUrl, $newEmail)
        );
    }

    /**
     * Verify and complete email change
     */
    public function verifyEmailChange(int $userId, string $token): bool
    {
        $user = User::findOrFail($userId);

        // Check if there's a pending email change
        if (!$user->pending_email || !$user->pending_email_token) {
            throw ValidationException::withMessages([
                'message' => 'No pending email change found.',
            ]);
        }

        // Verify token matches
        $hashedToken = hash('sha256', $token);
        if (!hash_equals($user->pending_email_token, $hashedToken)) {
            throw ValidationException::withMessages([
                'message' => 'Invalid verification token.',
            ]);
        }

        // Check if token has expired (60 minutes)
        if ($user->email_change_requested_at->diffInMinutes(now()) > 60) {
            throw ValidationException::withMessages([
                'message' => 'Verification link has expired. Please request a new email change.',
            ]);
        }

        // Overwrite email with pending email
        $user->update([
            'email' => $user->pending_email,
            'email_verified_at' => null, // Reset verification
            'pending_email' => null,
            'pending_email_token' => null,
            'email_change_requested_at' => null,
        ]);

        return true;
    }

    /**
     * Cancel pending email change
     */
    public function cancelEmailChange(User $user): void
    {
        if (!$user->pending_email) {
            throw ValidationException::withMessages([
                'message' => 'No pending email change to cancel.',
            ]);
        }

        $user->update([
            'pending_email' => null,
            'pending_email_token' => null,
            'email_change_requested_at' => null,
        ]);
    }


    private function createRefreshToken(User $user): string
    {
        do {
            $token = Str::random(64);
            $hashedToken = hash('sha256', $token);

            // Check if this hashed token already exists
            $exists = RefreshToken::where('token', $hashedToken)->exists();
        } while ($exists);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $hashedToken,
            'expires_at' => now()->addDays(30),
        ]);

        return $token;
    }
}
