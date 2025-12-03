<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TravelOrderStatusChanged extends Notification
{
    use Queueable;

    /**
     * Pedido atualizado.
     *
     * @var TravelOrder
     */
    protected $order;

    /**
     * Construtor padrão recebendo o pedido atualizado.
     */
    public function __construct(TravelOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Canais utilizados para a notificação.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Representação da notificação para armazenamento no banco.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'travel_order_id' => $this->order->id,
            'destination'     => $this->order->destination,
            'status'          => $this->order->status,
            'message'         => sprintf(
                'Seu pedido de viagem para %s foi %s.',
                $this->order->destination,
                $this->order->status
            ),
        ];
    }
}
