<?php

namespace App\Services;

use App\Models\FieldLocation;

class LocationRegistryService
{
    /**
     * Update a location's coordinates or geofence radius prospectively by adding a new location version.
     */
    public function updateGeofenceProspectively(
        FieldLocation $location,
        float $latitude,
        float $longitude,
        int $radiusMeters,
        ?string $reason = null
    ): FieldLocation {
        $now = now();

        // 1. Close current active location_version record if exists
        \DB::table('location_versions')
            ->where('field_location_id', $location->id)
            ->whereNull('effective_to')
            ->update(['effective_to' => $now]);

        // 2. Fetch latest version number
        $latestVersionNumber = \DB::table('location_versions')
            ->where('field_location_id', $location->id)
            ->max('version_number') ?? 0;

        $newVersionNumber = $latestVersionNumber + 1;

        // 3. Insert new prospective location_version
        \DB::table('location_versions')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'field_location_id' => $location->id,
            'version_number' => $newVersionNumber,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geofence_radius_meters' => $radiusMeters,
            'effective_from' => $now,
            'effective_to' => null,
            'change_reason' => $reason,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. Update current active coordinates on field_location header
        $location->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geofence_radius_meters' => $radiusMeters,
        ]);

        return $location;
    }

    /**
     * Resolve historical or active location version at a specific timestamp.
     */
    public function getActiveVersionAt(FieldLocation $location, \DateTimeInterface $timestamp)
    {
        return \DB::table('location_versions')
            ->where('field_location_id', $location->id)
            ->where('effective_from', '<=', $timestamp)
            ->where(function ($query) use ($timestamp) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $timestamp);
            })
            ->first();
    }
}
