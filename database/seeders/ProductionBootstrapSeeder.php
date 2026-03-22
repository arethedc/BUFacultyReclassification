<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentsSeeder::class,
            RankLevelsSeeder::class,
        ]);

        $email = trim((string) env('DEFAULT_ADMIN_EMAIL', ''));
        $password = (string) env('DEFAULT_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            throw new RuntimeException('DEFAULT_ADMIN_EMAIL and DEFAULT_ADMIN_PASSWORD must be set for ProductionBootstrapSeeder.');
        }
        if (strlen($password) < 12) {
            throw new RuntimeException('DEFAULT_ADMIN_PASSWORD must be at least 12 characters.');
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'role' => 'hr',
                'status' => 'active',
                'password' => Hash::make($password),
            ]
        );

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}
