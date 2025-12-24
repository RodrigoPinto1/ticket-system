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
use App\Models\InboxUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Simple controller to create tickets and send notifications.
 *
 * This is intentionally straightforward: validate input, create ticket,
 * generate ticket_number via observer, then notify requester, known emails and assignee.
 */
class TicketController extends Controller
{
    /**
     * Display a listing of tickets with filters and search.
     */
    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['inbox', 'requester', 'assignee', 'entity', 'type', 'status', 'contact']);

        // Restrict by user role
        $user = Auth::user();
        if ($user) {
            $isOperatorOrOwner = $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();
            if (!$isOperatorOrOwner) {
                // Clients can only see tickets where they are the requester
                $query->where('requester_id', $user->id);
                // And only tickets from their entity
                if ($user->entity_id) {
                    $query->where('entity_id', $user->entity_id);
                }
            }
        }

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
        // Only list users who are operators in any inbox
        $operators = User::select('id', 'name', 'email')
            ->whereHas('inboxRoles', function ($q) {
                $q->where('role', 'operator');
            })
            ->get();

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
        // List users who have operator role (any inbox)
        $operators = User::select('id', 'name', 'email')
            ->whereHas('inboxRoles', function ($q) {
                $q->where('role', 'operator');
            })
            ->get();

        // Operator assignments per inbox (to filter assignees by selected inbox on the client)
        $operatorAssignments = InboxUserRole::with(['user' => function ($q) {
            $q->select('id', 'name', 'email');
        }])
            ->where('role', 'operator')
            ->get()
            ->map(function (InboxUserRole $r) {
                return [
                    'inbox_id' => $r->inbox_id,
                    'user' => [
                        'id' => $r->user->id,
                        'name' => $r->user->name,
                        'email' => $r->user->email,
                    ],
                ];
            });

        // Check if current user is an operator or owner in any inbox
        $user = Auth::user();
        $isOperator = $user->inboxRoles()
            ->whereIn('role', ['operator', 'owner'])
            ->exists();

        // Determine default status id for 'pending'
        $defaultStatusId = TicketStatus::where('slug', 'pending')->value('id');

        return Inertia::render('Tickets/Create', [
            'inboxes' => $inboxes,
            'statuses' => $statuses,
            'types' => $types,
            'entities' => $entities,
            'operators' => $operators,
            'isOperator' => $isOperator,
            'defaultStatusId' => $defaultStatusId,
            'operatorAssignments' => $operatorAssignments,
        ]);
    }

    /**
     * Display a single ticket with full history and activity log.
     */
    public function show(Ticket $ticket)
    {
        // Enforce policy: clients must be requester to view
        $this->authorize('view', $ticket);

        $user = Auth::user();
        $isClient = $user ? $user->hasInboxRole($ticket->inbox_id, 'client') : false;
        $isOperator = $user ? $user->hasInboxRole($ticket->inbox_id, 'operator') : false;

        // Load relationships; omit activities for clients
        $relations = [
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
        ];
        if (!$isClient) {
            $relations[] = 'activities.user';
        }
        $ticket->load($relations);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
            'isClient' => $isClient,
            'isOperator' => $isOperator,
        ]);
    }

    /**
     * Show the form for editing a ticket.
     */
    public function edit(Ticket $ticket)
    {
        $ticket->load(['inbox', 'entity', 'type', 'status', 'assignee', 'requester']);

        $inboxes = Inbox::select('id', 'name')->get();
        $statuses = TicketStatus::select('id', 'name')->orderBy('order')->get();
        $types = TicketType::select('id', 'name')->get();
        $entities = Entity::select('id', 'name')->get();
        // Only operators for the ticket's inbox
        $operators = User::select('id', 'name', 'email')
            ->whereHas('inboxRoles', function ($q) use ($ticket) {
                $q->where('role', 'operator')
                    ->where('inbox_id', $ticket->inbox_id);
            })
            ->get();

        $user = Auth::user();
        // Only "operator" role should be considered operator for UI permissions
        $isOperator = $user->inboxRoles()->where('role', 'operator')->exists();

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket,
            'inboxes' => $inboxes,
            'statuses' => $statuses,
            'types' => $types,
            'entities' => $entities,
            'operators' => $operators,
            'isOperator' => $isOperator,
        ]);
    }

    /**
     * Store a new ticket.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate incoming request
        $data = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:inboxes,id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            // Only allow assigning to users who are operators in the selected inbox
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::exists('inbox_user_roles', 'user_id')
                    ->where(fn($q) => $q->where('inbox_id', $request->inbox_id)->where('role', 'operator')),
            ],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'type_id' => ['nullable', 'integer', 'exists:ticket_types,id'],
            'status_id' => ['nullable', 'integer', 'exists:ticket_statuses,id'],
            // known_emails accepts an array of email strings (CC)
            'known_emails' => ['nullable', 'array'],
            'known_emails.*' => ['email'],
        ]);

        // Check if user is operator or owner
        $isOperatorOrOwner = $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();

        // Only operators can assign tickets to someone
        if (!$isOperatorOrOwner && !empty($data['assigned_to'])) {
            abort(403, 'Apenas operadores podem atribuir responsáveis ao ticket');
        }

        // If user is a client, enforce entity restriction
        if (!$isOperatorOrOwner) {
            // Clients must create tickets for their own entity
            if ($user->entity_id && $data['entity_id'] && $data['entity_id'] !== $user->entity_id) {
                abort(403, 'Clientes só podem criar tickets para a sua entidade');
            }
            // If no entity specified, use user's entity
            if (!$data['entity_id'] && $user->entity_id) {
                $data['entity_id'] = $user->entity_id;
            }
        }

        // Ensure requester is the authenticated user (simple flow)
        $requesterId = Auth::id();
        $data['requester_id'] = $requesterId;

        // Resolve default status (Pendente) if not provided
        $statusId = $data['status_id'] ?? TicketStatus::where('slug', 'pending')->value('id');

        // Create the ticket (known_emails stored as JSON via model cast)
        $ticket = Ticket::create([
            'inbox_id' => $data['inbox_id'],
            'requester_id' => $data['requester_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'subject' => $data['subject'],
            'content' => $data['content'] ?? null,
            'known_emails' => $data['known_emails'] ?? null,
            'status_id' => $statusId,
            'type_id' => $data['type_id'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
        ]);

        // Ensure requester has 'client' role in this inbox to satisfy view/reply policies
        if (!InboxUserRole::where('inbox_id', $ticket->inbox_id)->where('user_id', $requesterId)->exists()) {
            InboxUserRole::create([
                'inbox_id' => $ticket->inbox_id,
                'user_id' => $requesterId,
                'role' => 'client',
            ]);
        }

        // Notify recipients with delayed sends to avoid provider rate limits.
        // Base delay is configured via MAIL_SEND_DELAY_MS (milliseconds) in .env.
        $baseDelayMs = (int) env('MAIL_SEND_DELAY_MS', 1000);
        $baseDelaySeconds = (int) max(1, ceil($baseDelayMs / 1000));

        // Collect all recipients
        $emailsToNotify = [];

        // 1. Notify the requester
        $requester = User::find($requesterId);
        if ($requester && !empty($requester->email)) {
            $emailsToNotify[] = $requester->email;
        }

        // 2. Notify inbox owners and operators
        $inboxOperators = User::whereHas('inboxRoles', function ($q) use ($ticket) {
            $q->where('inbox_id', $ticket->inbox_id)
                ->whereIn('role', ['owner', 'operator']);
        })->get();

        \Log::info('TicketController: Found operators for ticket', [
            'ticket_id' => $ticket->id,
            'inbox_id' => $ticket->inbox_id,
            'operator_count' => $inboxOperators->count(),
            'operator_emails' => $inboxOperators->pluck('email')->toArray(),
        ]);

        foreach ($inboxOperators as $operator) {
            if (!empty($operator->email) && $operator->id !== $requesterId && !in_array($operator->email, $emailsToNotify)) {
                $emailsToNotify[] = $operator->email;
            }
        }

        // 3. Notify known_emails/CC
        if (!empty($ticket->known_emails) && is_array($ticket->known_emails)) {
            foreach ($ticket->known_emails as $email) {
                if (!in_array($email, $emailsToNotify)) {
                    $emailsToNotify[] = $email;
                }
            }
        }

        // 4. Notify assignee
        if (!empty($ticket->assigned_to)) {
            $assignee = User::find($ticket->assigned_to);
            if ($assignee && !empty($assignee->email) && !in_array($assignee->email, $emailsToNotify)) {
                $emailsToNotify[] = $assignee->email;
            }
        }

        // Send emails with delays
        foreach ($emailsToNotify as $index => $email) {
            // Use much longer delays for Mailtrap sandbox (60s base = MAIL_SEND_DELAY_MS / 1000)
            $delaySeconds = ($index + 1) * $baseDelaySeconds;
            \Log::info('TicketController: Sending email', [
                'ticket_id' => $ticket->id,
                'recipient_email' => $email,
                'delay_seconds' => $delaySeconds,
            ]);

            // Dispatch job with proper delay
            dispatch(new \App\Jobs\SendTicketNotificationEmail($ticket, $email))
                ->delay(now()->addSeconds($delaySeconds));
        }

        // Redirect to the newly created ticket
        return redirect()->route('tickets.show', $ticket->id);
    }

    /**
     * Update the specified ticket in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        // General update authorization (owner/operator)
        $this->authorize('update', $ticket);

        $data = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:inboxes,id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            // Only allow assigning to users who are operators in the selected inbox
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::exists('inbox_user_roles', 'user_id')
                    ->where(fn($q) => $q->where('inbox_id', $request->inbox_id)->where('role', 'operator')),
            ],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'type_id' => ['nullable', 'integer', 'exists:ticket_types,id'],
            'status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
            'known_emails' => ['nullable', 'array'],
            'known_emails.*' => ['email'],
        ]);

        // Only operators can change status; if not operator, keep current status
        $user = Auth::user();
        $canChangeStatus = $user && $user->hasInboxRole($ticket->inbox_id, 'operator');
        $statusId = $canChangeStatus ? $data['status_id'] : $ticket->status_id;

        // Only operators can assign tickets
        $isOperator = $user && $user->hasInboxRole($ticket->inbox_id, 'operator');
        if (!$isOperator && isset($data['assigned_to']) && $data['assigned_to'] != $ticket->assigned_to) {
            abort(403, 'Apenas operadores podem atribuir responsáveis ao ticket');
        }

        $ticket->update([
            'inbox_id' => $data['inbox_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'subject' => $data['subject'],
            'content' => $data['content'] ?? null,
            'known_emails' => $data['known_emails'] ?? null,
            'status_id' => $statusId,
            'type_id' => $data['type_id'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
        ]);

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
            'attachments.*' => ['file', 'max:20480'], // Max 20MB per file
        ]);

        \Log::info('TicketController: Reply received', [
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'has_cc' => !empty($data['cc']),
            'cc' => $data['cc'] ?? [],
            'cc_count' => !empty($data['cc']) ? count($data['cc']) : 0,
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
