<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token_and_user_payload()
    {
        Storage::fake('public');

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'User Test',
            'email'                 => 'user@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'phone'                 => '11999999999',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'phone',
                    'avatar_url',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role'  => 'user',
        ]);
    }

    public function test_login_returns_token_and_user_data()
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('secret123'),
            'role'     => 'user',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'phone',
                    'avatar_url',
                    'created_at',
                ],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_validation_error()
    {
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_update_profile_and_password()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/profile', [
            'name'                  => 'New Name',
            'phone'                 => '11999998888',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'New Name')
            ->assertJsonPath('user.phone', '11999998888');

        $this->assertTrue(
            Hash::check('new-password', $user->fresh()->password)
        );
    }
}
