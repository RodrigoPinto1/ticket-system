<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\InboxUserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InboxController extends Controller
{
    /**
     * List inboxes the current user can manage and their roles.
     */
    public function manageRoles(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Operators and owners can VIEW roles; only owners can EDIT
        $hasAnyRole = InboxUserRole::where('user_id', $user->id)
            ->whereIn('role', ['operator', 'owner'])
            ->exists();

        if (!$hasAnyRole) {
            abort(403, 'Sem permissões para ver cargos.');
        }

        // Show all inboxes
        $inboxes = Inbox::with(['roles.user:id,name,email'])
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $isOwner = InboxUserRole::where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();

        // Only owners can edit any inbox
        $allowedInboxIds = $isOwner ? $inboxes->pluck('id') : collect([]);

        return Inertia::render('Inboxes/Roles', [
            'inboxes' => $inboxes,
            'users' => $users,
            'allowedInboxIds' => $allowedInboxIds,
            'canManage' => true,
            'canEdit' => $isOwner,
        ]);
    }

    /**
     * Assign or update a role for a user inside an inbox (operators/owners only).
     */
    public function assignRole(Request $request, Inbox $inbox)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Only owners can edit roles
        $isOwner = InboxUserRole::where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();

        if (!$isOwner) {
            abort(403, 'Apenas owners podem alterar cargos.');
        }

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:owner,operator,client'],
        ]);

        $assignment = InboxUserRole::updateOrCreate(
            [
                'inbox_id' => $inbox->id,
                'user_id' => $data['user_id'],
            ],
            [
                'role' => $data['role'],
            ]
        );

        return response()->json([
            'ok' => true,
            'assignment' => $assignment->load('user:id,name,email'),
        ]);
    }

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
