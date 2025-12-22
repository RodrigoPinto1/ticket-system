<?php

namespace App\Http\Controllers;

use App\Mail\TicketCreated;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Inbox;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Simple controller to create tickets and send notifications.
 *
 * This is intentionally straightforward: validate input, create ticket,
 * generate ticket_number via observer, then notify requester, known emails and assignee.
 */
class TicketController
{
    /**
     * Display a listing of tickets with filters and search.
     */
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['inbox', 'requester', 'assignee', 'entity', 'type', 'status', 'contact']);

        // Filter by Inbox
        if ($request->filled('inbox_id')) {
            $query->where('inbox_id', $request->inbox_id);
        }

        // Filter by Status
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // Filter by Operator/Assignee
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by Type
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter by Entity
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        // Quick search: Ticket Number, Subject, Email (known_emails or requester), Entity name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereJsonContains('known_emails', $search)
                    ->orWhereHas('requester', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('entity', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $tickets = $query->paginate(15)->withQueryString();

        // Load filter options for dropdowns
        $inboxes = Inbox::select('id', 'name')->get();
        $statuses = TicketStatus::select('id', 'name')->get();
        $types = TicketType::select('id', 'name')->get();
        $entities = Entity::select('id', 'name')->get();
        $operators = User::select('id', 'name', 'email')->get();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'inboxes' => $inboxes,
                'statuses' => $statuses,
                'types' => $types,
                'entities' => $entities,
                'operators' => $operators,
            ],
            'queryParams' => [
                'inbox_id' => $request->inbox_id,
                'status_id' => $request->status_id,
                'assigned_to' => $request->assigned_to,
                'type_id' => $request->type_id,
                'entity_id' => $request->entity_id,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        $inboxes = Inbox::select('id', 'name')->get();
        $statuses = TicketStatus::select('id', 'name')->get();
        $types = TicketType::select('id', 'name')->get();
        $entities = Entity::select('id', 'name')->get();
        $operators = User::select('id', 'name', 'email')->get();

        return Inertia::render('Tickets/Create', [
            'inboxes' => $inboxes,
            'statuses' => $statuses,
            'types' => $types,
            'entities' => $entities,
            'operators' => $operators,
        ]);
    }

    /**
     * Display a single ticket with full history and activity log.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'inbox',
            'requester',
            'assignee',
            'entity',
            'type',
            'status',
            'contact',
            'messages.user',
            'messages.contact',
            'messages.attachments',
            'activities.user',
        ]);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Store a new ticket.
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $data = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:inboxes,id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            // known_emails accepts an array of email strings
            'known_emails' => ['nullable', 'array'],
            'known_emails.*' => ['email'],
        ]);

        // Ensure requester is the authenticated user (simple flow)
        $requesterId = Auth::id();
        $data['requester_id'] = $requesterId;

        // Create the ticket (known_emails stored as JSON via model cast)
        $ticket = Ticket::create([
            'inbox_id' => $data['inbox_id'],
            'requester_id' => $data['requester_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'subject' => $data['subject'],
            'content' => $data['content'] ?? null,
            'known_emails' => $data['known_emails'] ?? null,
            'status_id' => null,
            'type_id' => null,
        ]);

        // Notify recipients by enqueuing Mailables with small delays to avoid provider rate limits.
        // Base delay is configured via MAIL_SEND_DELAY_MS (milliseconds) in .env.
        $baseDelayMs = (int) env('MAIL_SEND_DELAY_MS', 1000);
        $baseDelaySeconds = (int) max(1, ceil($baseDelayMs / 1000));
        $delayCounter = 0; // seconds to add for each recipient

        // Notify the requester (if email exists) - queued
        $requester = User::find($requesterId);
        if ($requester && !empty($requester->email)) {
            $delayCounter += $baseDelaySeconds;
            Mail::to($requester->email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
        }

        // Notify known_emails (queue individually, incrementing the delay)
        if (!empty($ticket->known_emails) && is_array($ticket->known_emails)) {
            foreach ($ticket->known_emails as $email) {
                $delayCounter += $baseDelaySeconds;
                Mail::to($email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
            }
        }

        // Notify assignee (if assigned)
        if (!empty($ticket->assigned_to)) {
            $assignee = User::find($ticket->assigned_to);
            if ($assignee && !empty($assignee->email)) {
                $delayCounter += $baseDelaySeconds;
                Mail::to($assignee->email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
            }
        }

        // Redirect to the newly created ticket
        return redirect()->route('tickets.show', $ticket->id);
    }

    /**
     * Reply to a ticket.
     * Creates a new message with support for text, images, and attachments.
     * Notifications are automatically sent via TicketMessageObserver.
     */
    public function reply(Request $request, Ticket $ticket)
    {
        // Validate incoming request
        $data = $request->validate([
            'content' => ['required', 'string'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // Max 10MB per file
        ]);

        // Create the message
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'contact_id' => null,
            'content' => $data['content'],
            'cc' => $data['cc'] ?? [],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Handle attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('ticket-attachments/' . $ticket->id, 'public');

                $message->attachments()->create([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Load relationships for response
        $message->load(['user', 'contact', 'attachments']);

        // Return response (Observer will handle notifications automatically)
        return response()->json([
            'success' => true,
            'message' => $message,
            'ticket_number' => $ticket->ticket_number,
        ]);
    }
}
