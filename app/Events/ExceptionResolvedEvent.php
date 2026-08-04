<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ExceptionResolvedEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $exceptionId,
        public string $code,
        public string $status,
        public string $reviewerName,
        public string $notes,
        public string $timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('exception-stream'),
            new Channel('dispatch-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->exceptionId,
            'code' => $this->code,
            'status' => $this->status,
            'reviewer_name' => $this->reviewerName,
            'notes' => $this->notes,
            'timestamp' => $this->timestamp,
        ];
    }
}
