<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetLifecycleService
{
    /**
     * Assign an asset to a user with digital signature sign-off.
     * Blocks re-assigning assets unless current state is 'in_inventory' or 'returned' (Rule 198).
     */
    public function assignAsset(Asset $asset, User $user, ?string $signature = null): AssetAssignment
    {
        // Rule 198: Block re-assigning active/issued/in_use assets
        if (!in_array($asset->status, ['in_inventory', 'returned'], true)) {
            throw new InvalidArgumentException("Asset '{$asset->name}' (Status: {$asset->status}) cannot be re-assigned until returned to inventory (Rule 198).");
        }

        return DB::transaction(function () use ($asset, $user, $signature) {
            $assignment = AssetAssignment::create([
                'asset_id' => $asset->id,
                'assigned_to_user_id' => $user->id,
                'assigned_at' => now(),
                'acceptance_signature' => $signature,
                'status' => 'active',
            ]);

            $asset->update(['status' => 'in_use']);

            return $assignment;
        });
    }

    /**
     * Process asset return to inventory.
     */
    public function returnAsset(Asset $asset): Asset
    {
        return DB::transaction(function () use ($asset) {
            $activeAssignment = AssetAssignment::where('asset_id', $asset->id)
                ->where('status', 'active')
                ->first();

            if ($activeAssignment) {
                $activeAssignment->update([
                    'returned_at' => now(),
                    'status' => 'returned',
                ]);
            }

            $asset->update(['status' => 'returned']);

            return $asset;
        });
    }
}
