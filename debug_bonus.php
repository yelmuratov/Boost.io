<?php

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the user
$user = \App\Models\User::where('email', 'elmuratovsal1mbay@gmail.com')->first();

if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User found: {$user->email}\n";
echo "Current Bonus Balance: {$user->bonus_balance}\n";
echo "Bonus Awarded Flag: " . ($user->bonus_awarded ? 'YES' : 'NO') . "\n";
echo "Email Verified At: " . ($user->email_verified_at ?? 'NULL') . "\n";

if ($user->hasVerifiedEmail()) {
    echo "Email already verified. Resetting verification to test bonus...\n";
    $user->email_verified_at = null;
    $user->bonus_awarded = false; // Reset flag to test logic!
    $user->bonus_balance = 0;     // Reset balance
    $user->save();
}

echo "Verifying email now...\n";

// Manually verify
$user->markEmailAsVerified();
event(new \Illuminate\Auth\Events\Verified($user));

$user->refresh();

echo "----------------------------------------\n";
echo "Post-Verification Status:\n";
echo "Email Verified At: {$user->email_verified_at}\n";
echo "Bonus Balance: {$user->bonus_balance}\n";
echo "Bonus Awarded Flag: " . ($user->bonus_awarded ? 'YES' : 'NO') . "\n";

if ($user->bonus_balance > 0) {
    echo "SUCCESS! Bonus was awarded correctly! 🎉\n";
} else {
    echo "FAILED! Bonus was NOT awarded. ❌\n";
}
