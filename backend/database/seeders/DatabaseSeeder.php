<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $testAccounts = [
            ['email' => 'ec@test.com', 'name' => 'Extension Coordinator', 'role' => 'extension_coordinator'],
            ['email' => 'leader@test.com', 'name' => 'Project Leader', 'role' => 'project_leader'],
            ['email' => 'beneficiary@test.com', 'name' => 'Beneficiary User', 'role' => 'beneficiary'],
            ['email' => 'evaluator@test.com', 'name' => 'Evaluator', 'role' => 'evaluator'],
        ];

        foreach ($testAccounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->update(['role' => $account['role']]);
        }
    }
}
