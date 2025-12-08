<?php

namespace Tests\Feature;

use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TravelOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    protected function createNormalUser(): User
    {
        return User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_non_admin_sees_only_his_own_travel_orders()
    {
        $user    = $this->createNormalUser();
        $another = $this->createNormalUser();

        // pedidos do próprio usuário
        $order1 = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'São Paulo',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-12',
            'status'         => 'solicitado',
        ]);

        $order2 = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Rio de Janeiro',
            'departure_date' => '2025-02-01',
            'return_date'    => '2025-02-03',
            'status'         => 'solicitado',
        ]);

        // pedido de outro usuário
        $foreignOrder = TravelOrder::create([
            'user_id'        => $another->id,
            'destination'    => 'Curitiba',
            'departure_date' => '2025-03-01',
            'return_date'    => '2025-03-05',
            'status'         => 'solicitado',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/travel-orders');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($order1->id, $ids);
        $this->assertContains($order2->id, $ids);
        $this->assertNotContains($foreignOrder->id, $ids);
    }

    public function test_admin_sees_all_travel_orders()
    {
        $admin = $this->createAdminUser();
        $user  = $this->createNormalUser();

        TravelOrder::create([
            'user_id'        => $admin->id,
            'destination'    => 'São Paulo',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-15',
            'status'         => 'solicitado',
        ]);

        TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Manaus',
            'departure_date' => '2025-02-10',
            'return_date'    => '2025-02-20',
            'status'         => 'solicitado',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/travel-orders');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_travel_orders_by_date_intersection()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Deve entrar no filtro
        $inside = TravelOrder::create([
            'user_id'        => $admin->id,
            'destination'    => 'Lisboa',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-20',
            'status'         => 'solicitado',
        ]);

        // Não deve entrar no filtro
        $outside = TravelOrder::create([
            'user_id'        => $admin->id,
            'destination'    => 'Paris',
            'departure_date' => '2025-02-01',
            'return_date'    => '2025-02-05',
            'status'         => 'solicitado',
        ]);

        $response = $this->getJson('/api/travel-orders?start_date=2025-01-12&end_date=2025-01-18');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_user_can_create_travel_order_with_default_status()
    {
        // Congela o "hoje" do teste
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->createNormalUser();
        Sanctum::actingAs($user);

        $payload = [
            'destination'    => 'São Paulo',
            'departure_date' => '2025-01-10', // >= today (2025-01-01)
            'return_date'    => '2025-01-12', // >= departure_date
        ];

        $response = $this->postJson('/api/travel-orders', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'destination' => 'São Paulo',
                'status'      => 'solicitado',
            ]);

        $this->assertDatabaseHas('travel_orders', [
            'user_id'     => $user->id,
            'destination' => 'São Paulo',
            'status'      => 'solicitado',
        ]);

        // Reseta o "agora" para não vazar pra outros testes
        Carbon::setTestNow();
    }


    public function test_non_admin_cannot_update_travel_order_status()
    {
        $user = $this->createNormalUser();
        $adminOrderOwner = $this->createNormalUser();

        $order = TravelOrder::create([
            'user_id'        => $adminOrderOwner->id,
            'destination'    => 'Curitiba',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-12',
            'status'         => 'solicitado',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'aprovado',
        ]);

        $response->assertStatus(403);

        $this->assertEquals('solicitado', $order->fresh()->status);
    }

        public function test_admin_can_approve_travel_order_and_send_notification()
        {
            Notification::fake();

            $admin = $this->createAdminUser();
            $user  = $this->createNormalUser();

            $order = TravelOrder::create([
                'user_id'        => $user->id,
                'destination'    => 'Porto Alegre',
                'departure_date' => '2025-01-10',
                'return_date'    => '2025-01-12',
                'status'         => 'solicitado',
            ]);

            Sanctum::actingAs($admin);

            $response = $this->patchJson("/api/travel-orders/{$order->id}/status", [
                'status' => 'aprovado',
            ]);

            $response
                ->assertOk()
                ->assertJsonFragment([
                    'status' => 'aprovado',
                ]);

            // Status realmente atualizado no banco
            $this->assertEquals('aprovado', $order->fresh()->status);

            // Verifica que a notificação certa foi enviada
            Notification::assertSentTo(
                $user,
                TravelOrderStatusChanged::class,
                function ($notification, $channels) use ($user, $order) {
                    // garante que está usando o canal "database"
                    $this->assertEquals(['database'], $channels);

                    // pega o payload exatamente como é salvo
                    $data = $notification->toDatabase($user);

                    return $data['travel_order_id'] === $order->id
                        && $data['old_status']      === 'solicitado'
                        && $data['new_status']      === 'aprovado'
                        && $data['destination']     === $order->destination
                        && $data['requested_at']    === optional($order->created_at)->toDateTimeString();
                }
            );
        }



    public function test_cannot_cancel_already_approved_travel_order()
    {
        $admin = $this->createAdminUser();
        $user  = $this->createNormalUser();

        $order = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Florianópolis',
            'departure_date' => '2025-01-10',
            'return_date'    => '2025-01-12',
            'status'         => 'aprovado',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'cancelado',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonFragment([
                'error' => 'Pedidos aprovados não podem ser cancelados.',
            ]);

        $this->assertEquals('aprovado', $order->fresh()->status);
    }


    public function test_cannot_create_travel_order_with_departure_date_in_the_past()
    {
        // congele o "hoje" para o teste ser estável
        Carbon::setTestNow('2025-01-10 12:00:00');

        $user = $this->createNormalUser();
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $payload = [
            'destination'    => 'São Paulo',
            // ontem em relação ao "hoje" do teste
            'departure_date' => '2025-01-09',
            'return_date'    => '2025-01-15',
        ];

        $response = $this->postJson('/api/travel-orders', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('departure_date');

        // garante que nada foi gravado
        $this->assertDatabaseCount('travel_orders', 0);

        Carbon::setTestNow();
    }


}
