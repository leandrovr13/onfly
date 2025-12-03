<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria um usuário administrador padrão para testes da API.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@onfly.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
    }
}
