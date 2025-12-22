<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\TicketController;

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

    // Create a new ticket
    Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');

    // Reply to a ticket
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
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
