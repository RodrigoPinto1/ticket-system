<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\InboxController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// Ticket management routes - Protected with auth middleware
Route::middleware(['auth'])->group(function () {
    // List all tickets with filters and search
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');

    // Show form for creating a new ticket
    Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');

    // View a single ticket with full history
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // Edit a ticket
    Route::get('tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');

    // Create a new ticket
    Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');

    // Update an existing ticket
    Route::put('tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');

    // Reply to a ticket
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');

    // Create a new entity
    Route::post('entities', [EntityController::class, 'store'])->name('entities.store');

    // Create a new ticket type
    Route::post('ticket-types', [TicketTypeController::class, 'store'])->name('ticket-types.store');

    // Create a new inbox (operators/owners only)
    Route::post('inboxes', [InboxController::class, 'store'])->name('inboxes.store');

    // Notification templates management
    Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
    Route::get('notification-templates/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('notification-templates.edit');
    Route::put('notification-templates/{template}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');
    Route::post('notification-templates/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('notification-templates.preview');

    // Preview email templates (legacy route)
    Route::get('email-preview/{slug}', function (string $slug) {
        $renderer = app(\App\Services\NotificationTemplateRenderer::class);

        $sampleData = [
            'ticket' => [
                'number' => 'TKT-12345',
                'subject' => 'Exemplo de ticket para preview',
                'content' => 'Esta é uma descrição de exemplo para visualizar o template.',
                'created_at' => now()->toDayDateTimeString(),
                'url' => url('/tickets/1'),
            ],
            'message' => [
                'content' => 'Esta é uma resposta de exemplo para visualizar o template de reply.',
                'created_at' => now()->toDayDateTimeString(),
            ],
            'app' => [
                'name' => config('app.name', 'Ticket System'),
            ],
        ];

        $rendered = $renderer->render($slug, $sampleData);

        if (!$rendered) {
            abort(404, 'Template não encontrado ou desativado');
        }

        return response($rendered['html'])->header('Content-Type', 'text/html');
    })->name('email.preview');
});

// Dev helper route: create a ticket quickly without authentication.
// This is ONLY registered in the local environment to help testing.
if (app()->environment('local')) {
    Route::get('dev/create-ticket', function (\Illuminate\Http\Request $request) {
        // create or reuse a dev inbox
        $inbox = \App\Models\Inbox::first() ?? \App\Models\Inbox::create(['name' => 'Dev Inbox', 'slug' => 'dev-inbox']);

        // create or reuse a dev user
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create([
            'email' => 'dev@example.com',
            'password' => bcrypt('password'),
        ]);

        // read params from query string or use defaults
        $subject = $request->query('subject', 'Dev ticket');
        $content = $request->query('content', 'Ticket criado via rota de desenvolvimento');
        $known = $request->query('known_emails');
        $known_emails = $known ? explode(',', $known) : ['foo@example.com'];
        $assigned_to = $request->query('assigned_to') ? intval($request->query('assigned_to')) : null;

        // create the ticket
        $ticket = \App\Models\Ticket::create([
            'inbox_id' => $inbox->id,
            'requester_id' => $user->id,
            'subject' => $subject,
            'content' => $content,
            'known_emails' => $known_emails,
            'assigned_to' => $assigned_to,
        ]);

        // refresh to get observer-generated ticket_number
        $ticket->refresh();

        return response()->json([
            'ok' => true,
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'known_emails' => $ticket->known_emails,
            ],
        ]);
    })->name('dev.create_ticket');
}
