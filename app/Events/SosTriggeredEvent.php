<?php

namespace App\Events;

use App\Models\SosRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosTriggeredEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SosRequest $sosRequest
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('emergency-alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.triggered';
    }
}
