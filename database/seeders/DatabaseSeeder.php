<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ServiceSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@goterapis.test'],
            [
                'name' => 'Admin GoTerapis',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $this->call(ArticleSeeder::class);
    }
}
