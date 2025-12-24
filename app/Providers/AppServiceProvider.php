<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Observers\TicketObserver;
use App\Observers\TicketMessageObserver;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Ticket::observe(TicketObserver::class);
        TicketMessage::observe(TicketMessageObserver::class);

        // Share auth role flags globally for frontend access control
        Inertia::share('authFlags', function () {
            $user = Auth::user();
            if (!$user) {
                return [
                    'isOperator' => false,
                    'isOwner' => false,
                    'isClient' => false,
                ];
            }

            $isOperator = $user->inboxRoles()->where('role', 'operator')->exists();
            $isOwner = $user->inboxRoles()->where('role', 'owner')->exists();
            $isClient = $user->inboxRoles()->where('role', 'client')->exists();

            return [
                'isOperator' => $isOperator,
                'isOwner' => $isOwner,
                'isClient' => $isClient,
            ];
        });
    }
}
