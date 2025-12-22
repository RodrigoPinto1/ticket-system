@component('mail::message')
# Nova resposta — {{ $ticket->ticket_number ?? 'Ticket' }}

Olá,

Uma nova resposta foi adicionada ao ticket **{{ $ticket->ticket_number }}**.

@component('mail::panel')
**Assunto:** {{ $ticket->subject }}
**Número:** {{ $ticket->ticket_number }}
**Data:** {{ optional($reply->created_at)->toDayDateTimeString() ?? '' }}
@endcomponent

### Mensagem
{!! nl2br(e($reply->content)) !!}

@if(isset($reply->attachments) && $reply->attachments->count() > 0)
### Anexos ({{ $reply->attachments->count() }})
@component('mail::table')
| Nome | Tamanho |
|:-----|:--------|
@foreach($reply->attachments as $attachment)
| {{ $attachment->file_name }} | {{ number_format($attachment->file_size / 1024, 2) }} KB |
@endforeach
@endcomponent
@endif

@if(!empty($reply->cc) && is_array($reply->cc) && count($reply->cc) > 0)
**CC:** {{ implode(', ', $reply->cc) }}
@endif

@php
    $url = rtrim(config('app.url', env('APP_URL', 'https://ticket-system.test/')), '/') . '/tickets/' . ($ticket->id ?? '');
@endphp

@component('mail::button', ['url' => $url])
Ver ticket completo
@endcomponent

Este é um e-mail automático. Para responder, acesse o ticket através do link acima.

Obrigado,
{{ config('app.name', 'Ticket System') }}
@endcomponent
