<?php

namespace App\Providers;

use App\Models\Inbox;
use App\Models\Ticket;
use App\Policies\InboxPolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Inbox::class => InboxPolicy::class,
        Ticket::class => TicketPolicy::class,
    ];

    public function boot(): void
    {
        // Policies are registered via $policies property.
    }
}
