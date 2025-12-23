<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Pendente',
                'slug' => 'pending',
                'description' => 'Aguardando triagem/tratamento',
                'color' => '#EAB308',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Em Análise',
                'slug' => 'in-analysis',
                'description' => 'Em análise pela equipa',
                'color' => '#3B82F6',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Concluído',
                'slug' => 'completed',
                'description' => 'Ticket resolvido/encerrado',
                'color' => '#22C55E',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $data) {
            TicketStatus::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
