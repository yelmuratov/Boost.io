<?php
namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService{
    public function register(array $data):array
    {
        $user = User::create([
            'user_name' => $data['user_name'],
            'email' => $data['email'],
            'telegram_username' => $data['telegram_username'] ?? 'null',
            'password' => Hash::make($data['password']),
        ]);

        $accessToken = $user->createToken('authToken')->plainTextToken;
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => '60 minutes'
        ];
    }

    public function login(array $credentials):array
    {
        $user = User::where('user_name',$credentials['user_name'])->first();

        if(!$user || !Hash::check($credentials['password'], $user->password)){
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

    public function refreshToken(string $refreshToken):array
    {
        $token = RefreshToken::where('token',hash('sha256', $refreshToken))->first();
        if(!$token || $token->isExpired()){
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
