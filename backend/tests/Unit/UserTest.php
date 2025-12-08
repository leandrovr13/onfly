<?php

namespace Tests\Unit;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_returns_true_when_role_is_admin()
    {

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_is_admin_returns_false_when_role_is_not_admin()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_user_has_many_travel_orders_relationship()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

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
            'return_date'    => '2025-02-05',
            'status'         => 'aprovado',
        ]);

        $this->assertCount(2, $user->travelOrders);
        $this->assertTrue($user->travelOrders->contains($order1));
        $this->assertTrue($user->travelOrders->contains($order2));
    }
}
