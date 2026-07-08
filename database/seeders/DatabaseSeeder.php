<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'dueno@controlpresta.com'],
            ['name' => 'Dueño', 'password' => 'password']
        );

        User::firstOrCreate(
            ['email' => 'esposa@controlpresta.com'],
            ['name' => 'Esposa', 'password' => 'password']
        );

        $this->call(ClientesPruebaSeeder::class);
    }
}
