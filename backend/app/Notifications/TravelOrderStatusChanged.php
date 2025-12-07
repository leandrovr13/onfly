<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChanged extends Notification
{
    use Queueable;

    protected TravelOrder $travelOrder;
    protected string $oldStatus;
    protected string $newStatus;

    public function __construct(TravelOrder $travelOrder, string $oldStatus, string $newStatus)
    {
        $this->travelOrder = $travelOrder;
        $this->oldStatus   = $oldStatus;
        $this->newStatus   = $newStatus;
    }

    public function via($notifiable): array
    {
        // Só banco por enquanto
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'travel_order_id' => $this->travelOrder->id,
            'old_status'      => $this->oldStatus,
            'new_status'      => $this->newStatus,
            'destination'     => $this->travelOrder->destination ?? null,
            'requested_at'    => optional($this->travelOrder->created_at)->toDateTimeString(),
        ];
    }
}
