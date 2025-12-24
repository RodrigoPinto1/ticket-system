<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed initial data
        $this->call([
            InboxesSeeder::class,
            TicketStatusSeeder::class,
            InboxUserRoleSeeder::class,
            OperatorAssignmentsSeeder::class,
            NotificationTemplateSeeder::class,
        ]);
    }
}
