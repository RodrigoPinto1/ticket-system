<?php

namespace Database\Seeders;

use App\Models\Inbox;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InboxesSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Comercial'],
            ['name' => 'Apoio Técnico'],
            ['name' => 'Recursos Humanos'],
        ];

        foreach ($departments as $dept) {
            $name = $dept['name'];
            $slug = Str::slug($name, '-');

            Inbox::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $name . ' — Departamento',
                    'is_active' => true,
                ]
            );
        }
    }
}
