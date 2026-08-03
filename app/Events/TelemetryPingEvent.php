<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TelemetryPingEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $repId,
        public string $repName,
        public string $outletName,
        public float $latitude,
        public float $longitude,
        public string $timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('telemetry-stream'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'rep_id' => $this->repId,
            'rep_name' => $this->repName,
            'outlet_name' => $this->outletName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => $this->timestamp,
        ];
    }
}
