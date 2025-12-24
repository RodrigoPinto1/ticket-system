<?php

namespace Database\Seeders;

use App\Models\Inbox;
use App\Models\InboxUserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class OperatorAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'comercial' => [
                'name' => 'Operador Comercial',
                'email' => 'comercial.operator@example.com',
            ],
            'apoio-tecnico' => [
                'name' => 'Operador Apoio Técnico',
                'email' => 'apoio.operator@example.com',
            ],
            'recursos-humanos' => [
                'name' => 'Operador RH',
                'email' => 'rh.operator@example.com',
            ],
        ];

        foreach ($map as $slug => $userData) {
            $inbox = Inbox::where('slug', $slug)->first();
            if (!$inbox) {
                // Skip if inbox missing (should be seeded by InboxesSeeder)
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                ]
            );

            InboxUserRole::firstOrCreate(
                [
                    'inbox_id' => $inbox->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'operator',
                ]
            );
        }
    }
}
