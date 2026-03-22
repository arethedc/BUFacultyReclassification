<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'System Admin', 'email' => 'admin@gmail.com', 'role' => 'hr'],
            ['name' => 'System Dean', 'email' => 'dean@gmail.com', 'role' => 'dean'],
            ['name' => 'System VPAA', 'email' => 'vpaa@gmail.com', 'role' => 'vpaa'],
            ['name' => 'System President', 'email' => 'president@gmail.com', 'role' => 'president'],
        ];

        $allowedEmails = array_map(
            static fn (array $account): string => $account['email'],
            $accounts
        );

        $shouldPruneUsers = filter_var((string) env('SEED_PRUNE_USERS', 'false'), FILTER_VALIDATE_BOOL);
        if ($shouldPruneUsers) {
            User::query()->whereNotIn('email', $allowedEmails)->delete();
        }

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'status' => 'active',
                    'department_id' => null,
                    'password' => Hash::make('Admin123!'),
                ]
            );

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
