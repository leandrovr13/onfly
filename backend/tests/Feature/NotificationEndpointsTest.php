<?php

namespace Tests\Feature;

use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications_as_read()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Sanctum::actingAs($user);

        // cria um pedido e uma notificação real em banco
        $order = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Recife',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-12',
            'status'         => 'aprovado',
        ]);

        $user->notify(new TravelOrderStatusChanged($order, 'solicitado', 'aprovado'));

        $this->assertCount(1, $user->notifications);

        // lista notificações
        $listResponse = $this->getJson('/api/notifications');
        $listResponse
            ->assertOk()
            ->assertJsonCount(1);

        $notificationId = $listResponse->json('0.id');

        // marca como lida
        $readResponse = $this->postJson("/api/notifications/{$notificationId}/read");

        $readResponse
            ->assertOk()
            ->assertJsonFragment(['status' => 'ok']);

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }
}
