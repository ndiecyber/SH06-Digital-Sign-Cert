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
        // Create Admin User
        $admin = User::factory()->create([
            'name' => 'Rizky Pratama',
            'email' => 'admin@lexa.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create Regular User
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'user@lexa.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Generate dummy data using factories
        \App\Models\Document::factory(10)->create();
        \App\Models\Certificate::factory(10)->create();
        \App\Models\Signature::factory(10)->create();
        \App\Models\ActivityLog::factory(20)->create();
    }
}