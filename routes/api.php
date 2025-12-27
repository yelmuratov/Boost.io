<?php

use App\Http\Controllers\API\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\SmmProviderController;
use Illuminate\Http\Request;
use App\Models\User;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

// Email verification - clicked from email (no auth required, signed URL provides security)
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    try {
        // Check if signature is valid first
        if (!$request->hasValidSignature()) {
            \Log::error('Email verification failed: Invalid signature');
            return view('auth.email-verified', [
                'success' => false,
                'message' => 'Verification link has expired or is invalid. Please request a new verification email.',
            ]);
        }

        $user = User::findOrFail($id);

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return view('auth.email-verified', [
                'success' => true,
                'message' => 'Email already verified!',
            ]);
        }

        // Verify the hash matches
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            \Log::error('Email verification failed: Hash mismatch', ['user_id' => $id]);
            return view('auth.email-verified', [
                'success' => false,
                'message' => 'Invalid verification link.',
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Fire the Verified event to trigger bonus award
        event(new \Illuminate\Auth\Events\Verified($user));

        \Log::info('Email verified successfully', ['user_id' => $id]);

        // Refresh user to get new bonus_awarded state
        $user->refresh();

        // Check if bonus system is enabled by admin
        $bonusEnabled = \App\Models\SystemSetting::get('bonus.enabled', true);

        $viewData = ['success' => true];

        // Show bonus info if user just received bonus (first verification)
        if ($bonusEnabled && $user->bonus_awarded && $user->bonus_balance > 0) {
            $bonusAmount = \App\Models\SystemSetting::get('bonus.registration_amount', 5000);
            $unlockThreshold = \App\Models\SystemSetting::get('bonus.unlock_threshold', 10000);

            $viewData['bonusAmount'] = number_format((float) $bonusAmount, 0);
            $viewData['unlockThreshold'] = number_format((float) $unlockThreshold, 0);
        }

        return view('auth.email-verified', $viewData);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        \Log::error('Email verification failed: User not found', ['id' => $id]);
        return view('auth.email-verified', [
            'success' => false,
            'message' => 'User not found.',
        ]);
    } catch (\Exception $e) {
        \Log::error('Email verification failed: ' . $e->getMessage(), ['id' => $id]);
        return view('auth.email-verified', [
            'success' => false,
            'message' => 'An error occurred during verification.',
        ]);
    }
})->name('verification.verify');

// Email change verification - no auth required (uses token for security)
Route::get('/email/verify-change/{userId}/{token}', [\App\Http\Controllers\API\Auth\EmailChangeController::class, 'verifyChange'])
    ->name('email.verify-change');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);

    // Resend verification email (requires auth)
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified',
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();
        return response()->json([
            'success' => true,
            'message' => 'Verification link sent',
        ]);
    })->middleware('throttle:6,1')->name('verification.send');

    // Email change routes
    Route::post('/email/change-request', [\App\Http\Controllers\API\Auth\EmailChangeController::class, 'requestChange'])
        ->middleware('throttle:3,1440'); // 3 per 24 hours

    Route::post('/email/cancel-change', [\App\Http\Controllers\API\Auth\EmailChangeController::class, 'cancelChange']);

    // User wallet routes
    Route::get('/wallet', [\App\Http\Controllers\API\WalletController::class, 'index']);
    Route::get('/wallet/transactions', [\App\Http\Controllers\API\WalletController::class, 'transactions']);

    // User order routes (require verified email)
    Route::middleware('verified')->group(function () {
        Route::post('/orders', [\App\Http\Controllers\API\OrderController::class, 'store']);
        Route::get('/orders', [\App\Http\Controllers\API\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\API\OrderController::class, 'show']);
    });
});


// Admin routes - require auth, verified email, and admin role
Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin/providers')->group(function () {
    // CRUD operations
    Route::get('/', [SmmProviderController::class, 'index']);
    Route::post('/', [SmmProviderController::class, 'store']);
    Route::get('/{id}', [SmmProviderController::class, 'show']);
    Route::put('/{id}', [SmmProviderController::class, 'update']);
    Route::delete('/{id}', [SmmProviderController::class, 'destroy']);

    // Actions
    Route::post('/{id}/sync-services', [SmmProviderController::class, 'syncServices']);
    Route::post('/{id}/sync-balance', [SmmProviderController::class, 'syncBalance']);
    Route::post('/{id}/test-connection', [SmmProviderController::class, 'testConnection']);
    Route::post('/{id}/toggle-active', [SmmProviderController::class, 'toggleActive']);
    Route::get('/{id}/stats', [SmmProviderController::class, 'stats']);
});

// =========================
// Admin Services Management
// =========================
Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin/services')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'destroy']);

    // Actions
    Route::post('/{id}/toggle-active', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'toggleActive']);
    Route::post('/bulk-update', [\App\Http\Controllers\API\Admin\SmmServiceController::class, 'bulkUpdate']);
});

// =========================
// Admin Order Management
// =========================
Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin/orders')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\Admin\OrderController::class, 'index']);
    Route::get('/stats/summary', [\App\Http\Controllers\API\Admin\OrderController::class, 'stats']);
    Route::get('/{id}', [\App\Http\Controllers\API\Admin\OrderController::class, 'show']);
});

// =========================
// Admin Wallet Management
// =========================
Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin/users')->group(function () {
    Route::get('/{id}/wallet', [\App\Http\Controllers\API\Admin\WalletController::class, 'show']);
    Route::get('/{id}/transactions', [\App\Http\Controllers\API\Admin\WalletController::class, 'transactions']);
    Route::post('/{id}/wallet/adjust', [\App\Http\Controllers\API\Admin\WalletController::class, 'adjust']);
});

// =========================
// Admin System Settings
// =========================
Route::middleware(['auth:sanctum', 'verified', 'admin'])->prefix('admin/settings')->group(function () {
    Route::get('/bonus', [\App\Http\Controllers\API\Admin\SystemSettingController::class, 'getBonusSettings']);
    Route::put('/bonus', [\App\Http\Controllers\API\Admin\SystemSettingController::class, 'updateBonusSettings']);
});

// =========================
// Public Services API (no auth required)
// =========================
Route::prefix('services')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\SmmServiceController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\API\SmmServiceController::class, 'show']);
});
