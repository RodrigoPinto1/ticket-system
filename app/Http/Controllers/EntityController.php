<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntityController extends Controller
{
    /**
     * Store a newly created entity in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nif' => 'required|string|unique:entities,nif',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'internal_notes' => 'nullable|string',
        ]);

        $entity = Entity::create($validated);

        return response()->json([
            'entity' => $entity,
            'message' => 'Entidade criada com sucesso',
        ]);
    }
}
