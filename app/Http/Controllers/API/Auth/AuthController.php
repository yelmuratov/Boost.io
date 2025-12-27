<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ){}

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_name' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $result = $this->authService->register($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'User registered successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'user_name' => 'required|min:3|max:255|string',
                'password' => 'required|string',
            ]);

            $tokens = $this->authService->login($credentials);

            return response()->json([
                'success' => true,
                'data' => $tokens,
                'message' => 'Login successful'
            ], 200);

        } catch (ValidationException $e) {
            // Handle both validation errors and invalid credentials
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
            ], 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            $tokens = $this->authService->refreshToken($request->refresh_token);

            return response()->json([
                'success' => true,
                'data' => $tokens,
                'message' => 'Token refreshed successfully'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ], 200);
    }

}
