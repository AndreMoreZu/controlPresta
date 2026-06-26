<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
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
    }
}
