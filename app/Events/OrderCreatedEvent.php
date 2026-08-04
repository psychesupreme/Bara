<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderCreatedEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $customerName,
        public float $totalAmount,
        public string $status,
        public string $timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('dispatch-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'customer_name' => $this->customerName,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
        ];
    }
}
