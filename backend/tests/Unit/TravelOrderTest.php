<?php

namespace Tests\Unit;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_travel_order_belongs_to_user_relationship()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Curitiba',
            'departure_date' => '2025-03-10',
            'return_date'    => '2025-03-15',
            'status'         => 'solicitado',
        ]);

        $this->assertTrue($order->user->is($user));
    }

    public function test_dates_are_cast_to_carbon_instances()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = TravelOrder::create([
            'user_id'        => $user->id,
            'destination'    => 'Florianópolis',
            'departure_date' => '2025-04-01',
            'return_date'    => '2025-04-05',
            'status'         => 'solicitado',
        ]);

        $this->assertInstanceOf(Carbon::class, $order->departure_date);
        $this->assertInstanceOf(Carbon::class, $order->return_date);

        $this->assertTrue($order->departure_date->isSameDay(Carbon::parse('2025-04-01')));
        $this->assertTrue($order->return_date->isSameDay(Carbon::parse('2025-04-05')));
    }
}
