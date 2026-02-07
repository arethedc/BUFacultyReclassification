<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */
        $departments = [
            'CITE',
            'CEDE',
            'CLAGE',
            'CBAA',
            'CNAHS',
            'CEHD',
        ];

        $departmentMap = [];

        foreach ($departments as $deptName) {
            $dept = Department::updateOrCreate(
                ['name' => $deptName],
                ['name' => $deptName]
            );

            // store id for later use
            $departmentMap[$deptName] = $dept->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */
        $password = Hash::make('test1234'); // same password for all test users

        $users = [
            [
                'name' => 'Test Faculty',
                'email' => 'faculty@test.com',
                'role' => 'faculty',
                'department' => 'CITE',
            ],
            [
                'name' => 'Test Dean',
                'email' => 'dean@test.com',
                'role' => 'dean',
                'department' => 'CEDE',
            ],
            [
                'name' => 'Test HR',
                'email' => 'hr@test.com',
                'role' => 'hr',
                'department' => null,
            ],
            [
                'name' => 'Test VPAA',
                'email' => 'vpaa@test.com',
                'role' => 'vpaa',
                'department' => null,
            ],
            [
                'name' => 'Test President',
                'email' => 'president@test.com',
                'role' => 'president',
                'department' => null,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'status' => 'active',
                    'department_id' => $user['department']
                        ? $departmentMap[$user['department']]
                        : null,
                    'password' => $password,
                ]
            );
        }
    }
}
