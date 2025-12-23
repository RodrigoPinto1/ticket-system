<?php

namespace Database\Seeders;

use App\Models\Inbox;
use App\Models\User;
use App\Models\InboxUserRole;
use Illuminate\Database\Seeder;

class InboxUserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Get first inbox or create one
        $inbox = Inbox::first();
        if (!$inbox) {
            $inbox = Inbox::create([
                'name' => 'Default Inbox',
                'slug' => 'default-inbox',
            ]);
        }

        // Get all users
        $users = User::all();

        // Assign each user to the inbox
        foreach ($users as $user) {
            InboxUserRole::firstOrCreate(
                [
                    'inbox_id' => $inbox->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => 'operator', // Default role
                ]
            );
        }
    }
}
