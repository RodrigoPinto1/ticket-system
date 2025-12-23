<?php

namespace App\Http\Controllers;

use App\Models\TicketType;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    /**
     * Store a newly created ticket type in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:ticket_types,name|max:255',
            'description' => 'nullable|string',
        ]);

        $type = TicketType::create($validated);

        return response()->json([
            'type' => $type,
            'message' => 'Tipo criado com sucesso',
        ]);
    }
}
