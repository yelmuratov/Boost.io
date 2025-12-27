<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailChangeController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    /**
     * Request email change
     * Rate limited to 3 requests per 24 hours
     */
    public function requestChange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_email' => 'required|string|email|max:255',
            ]);

            $this->authService->requestEmailChange($request->user(), $validated['new_email']);

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent to your new email address. Please check your inbox.',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request email change',
            ], 500);
        }
    }

    /**
     * Verify email change with token
     */
    public function verifyChange(Request $request, string $userId, string $token)
    {
        try {
            $success = $this->authService->verifyEmailChange((int) $userId, $token);

            // Get the user and send verification email to NEW address
            $user = \App\Models\User::findOrFail($userId);
            $user->sendEmailVerificationNotification();

            return view('auth.email-change-verified', [
                'success' => $success,
                'message' => 'Email address updated successfully! Check your inbox for verification email.',
            ]);

        } catch (ValidationException $e) {
            return view('auth.email-change-verified', [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            \Log::error('Email change verification failed: ' . $e->getMessage());
            return view('auth.email-change-verified', [
                'success' => false,
                'message' => 'An error occurred during verification.',
            ]);
        }
    }

    /**
     * Cancel pending email change
     */
    public function cancelChange(Request $request): JsonResponse
    {
        try {
            $this->authService->cancelEmailChange($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Email change request cancelled successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel email change',
            ], 500);
        }
    }
}
