<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Support\Facades\Auth;

/**
 * Observer to populate ticket_number after a Ticket is created.
 * We use the auto-increment id to produce a stable, sequential ticket number.
 */
class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // If ticket_number not provided, generate using ID with zero padding
        if (empty($ticket->ticket_number)) {
            $ticket->ticket_number = 'TC-' . str_pad($ticket->id, 6, '0', STR_PAD_LEFT);
            // saveQuietly avoids firing events again
            $ticket->saveQuietly();
        }

        // Log ticket creation activity
        TicketActivity::log(
            ticketId: $ticket->id,
            action: 'created',
            userId: Auth::id(),
            description: "Ticket criado: {$ticket->ticket_number}",
            metadata: [
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
            ]
        );
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Track changes to important fields
        $changes = $ticket->getDirty();
        $original = $ticket->getOriginal();

        foreach ($changes as $field => $newValue) {
            // Skip timestamps and non-important fields
            if (in_array($field, ['updated_at', 'created_at'])) {
                continue;
            }

            $oldValue = $original[$field] ?? null;
            $description = $this->getChangeDescription($field, $oldValue, $newValue, $ticket);

            TicketActivity::log(
                ticketId: $ticket->id,
                action: 'updated',
                userId: Auth::id(),
                field: $field,
                oldValue: $this->formatValue($oldValue),
                newValue: $this->formatValue($newValue),
                description: $description
            );
        }
    }

    /**
     * Generate human-readable description for field changes.
     */
    private function getChangeDescription(string $field, $oldValue, $newValue, Ticket $ticket): string
    {
        return match ($field) {
            'status_id' => $this->getStatusChangeDescription($oldValue, $newValue),
            'assigned_to' => $this->getAssignmentChangeDescription($oldValue, $newValue),
            'type_id' => $this->getTypeChangeDescription($oldValue, $newValue),
            'entity_id' => $this->getEntityChangeDescription($oldValue, $newValue),
            'subject' => "Assunto alterado",
            'closed_at' => $newValue ? "Ticket fechado" : "Ticket reaberto",
            default => "Campo '{$field}' atualizado",
        };
    }

    private function getStatusChangeDescription($oldId, $newId): string
    {
        if (!$newId) return "Estado removido";

        $newStatus = \App\Models\TicketStatus::find($newId);
        if ($oldId) {
            $oldStatus = \App\Models\TicketStatus::find($oldId);
            return "Estado alterado de '{$oldStatus?->name}' para '{$newStatus?->name}'";
        }

        return "Estado definido como '{$newStatus?->name}'";
    }

    private function getAssignmentChangeDescription($oldId, $newId): string
    {
        if (!$newId) return "Atribuição removida";

        $newUser = \App\Models\User::find($newId);
        if ($oldId) {
            $oldUser = \App\Models\User::find($oldId);
            return "Reatribuído de '{$oldUser?->name}' para '{$newUser?->name}'";
        }

        return "Atribuído a '{$newUser?->name}'";
    }

    private function getTypeChangeDescription($oldId, $newId): string
    {
        if (!$newId) return "Tipo removido";

        $newType = \App\Models\TicketType::find($newId);
        if ($oldId) {
            $oldType = \App\Models\TicketType::find($oldId);
            return "Tipo alterado de '{$oldType?->name}' para '{$newType?->name}'";
        }

        return "Tipo definido como '{$newType?->name}'";
    }

    private function getEntityChangeDescription($oldId, $newId): string
    {
        if (!$newId) return "Entidade removida";

        $newEntity = \App\Models\Entity::find($newId);
        if ($oldId) {
            $oldEntity = \App\Models\Entity::find($oldId);
            return "Entidade alterada de '{$oldEntity?->name}' para '{$newEntity?->name}'";
        }

        return "Entidade definida como '{$newEntity?->name}'";
    }

    private function formatValue($value): ?string
    {
        if ($value === null) return null;
        if (is_array($value)) return json_encode($value);
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string) $value;
    }
}

