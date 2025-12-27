<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\BonusService;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function __construct(
        private BonusService $bonusService
    ) {
    }

    /**
     * Get bonus configuration
     */
    public function getBonusSettings()
    {
        $config = $this->bonusService->getBonusConfig();

        return response()->json([
            'success' => true,
            'settings' => $config,
        ]);
    }

    /**
     * Update bonus configuration
     */
    public function updateBonusSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'sometimes|boolean',
            'registration_amount' => 'sometimes|numeric|min:0',
            'unlock_threshold' => 'sometimes|numeric|min:0',
        ]);

        $this->bonusService->updateBonusConfig($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bonus settings updated successfully',
            'settings' => $this->bonusService->getBonusConfig(),
        ]);
    }
}
