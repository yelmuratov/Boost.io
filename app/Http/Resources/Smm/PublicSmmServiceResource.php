<?php

namespace App\Http\Resources\Smm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicSmmServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'type' => $this->type,

            // CLEANED category (no provider, no emojis)
            'category' => $this->cleanCategory($this->category),

            'rate' => (float) $this->rate,
            'min'  => (int) $this->min,
            'max'  => (int) $this->max,

            'is_active'  => (bool) $this->is_active,
            'description' => $this->description,

            'features' => [
                'drip feed' => (bool) ($this->metadata['dripfeed'] ?? false),
                'refill'   => (bool) ($this->metadata['refill'] ?? false),
                'cancel'   => (bool) ($this->metadata['cancel'] ?? false),
            ],
        ];
    }

    private function cleanCategory(?string $category): ?string
    {
        if (! $category) {
            return null;
        }

        // Remove emojis
        $category = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $category);

        // Remove provider branding & ranking
        $category = preg_replace('/^#?\d*\s*BulkMedya\s*/i', '', $category);

        return trim($category);
    }
}
