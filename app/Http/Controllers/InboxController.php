<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\InboxUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InboxController extends Controller
{
    /**
     * Store a newly created inbox.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // Generate a unique slug from the name
        $base = Str::slug($data['name']);
        $slug = $base;
        $counter = 1;
        while (Inbox::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        $inbox = Inbox::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        // Assign the creator a role in the new inbox
        $role = InboxUserRole::where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists() ? 'owner' : 'operator';
        InboxUserRole::firstOrCreate([
            'inbox_id' => $inbox->id,
            'user_id' => $user->id,
        ], [
            'role' => $role,
        ]);

        return response()->json(['inbox' => $inbox]);
    }
}
