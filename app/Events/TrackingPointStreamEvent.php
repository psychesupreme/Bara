<?php

namespace App\Events;

use App\Models\TrackingPoint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackingPointStreamEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TrackingPoint $trackingPoint
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('live-telemetry'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'telemetry.point';
    }
}
