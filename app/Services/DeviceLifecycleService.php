<?php

namespace App\Services;

use App\Models\FieldDevice;
use App\Models\User;
use Illuminate\Support\Str;

class DeviceLifecycleService
{
    /**
     * Register a new field device for approval.
     */
    public function registerDevice(
        User $user,
        string $deviceUuid,
        ?string $model = null,
        ?string $osVersion = null,
        ?string $appVersion = null,
        ?string $publicKeyHash = null
    ): FieldDevice {
        return FieldDevice::updateOrCreate(
            ['device_uuid' => $deviceUuid],
            [
                'client_uuid' => (string) Str::uuid(),
                'sequence' => 1,
                'user_id' => $user->id,
                'model' => $model,
                'os_version' => $osVersion,
                'app_version' => $appVersion,
                'status' => 'pending_approval',
                'public_key_hash' => $publicKeyHash,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Approve a registered field device.
     */
    public function approveDevice(FieldDevice $device): FieldDevice
    {
        $device->update([
            'status' => 'approved',
        ]);

        return $device;
    }

    /**
     * Revoke a registered field device immediately.
     */
    public function revokeDevice(FieldDevice $device): FieldDevice
    {
        $device->update([
            'status' => 'revoked',
        ]);

        return $device;
    }

    /**
     * Check if a device UUID is valid and approved.
     */
    public function isDeviceApproved(string $deviceUuid): bool
    {
        $device = FieldDevice::where('device_uuid', $deviceUuid)->first();
        return $device && $device->status === 'approved';
    }
}
