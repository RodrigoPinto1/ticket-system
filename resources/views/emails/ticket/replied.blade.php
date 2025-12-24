<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nova resposta - {{ $ticket->ticket_number ?? 'N/A' }}</title>
</head>
<body class="bg-gray-100 antialiased">
    <div class="max-w-3xl mx-auto my-6 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-emerald-400 text-white p-4">
            <h1 class="text-lg font-semibold">Ticket {{ $ticket->ticket_number ?? 'N/A' }} — Nova resposta:</h1>
            <p class="text-sm mt-2 text-green-100">{{ $ticket->subject }}</p>
        </div>

        <div class="p-6 text-gray-800">
            <p class="mb-2">Olá,</p>
            <p class="mb-4 text-gray-700">Uma nova resposta foi adicionada ao ticket <strong>{{ $ticket->ticket_number }}</strong>.</p>

            <div class="bg-gray-50 border border-gray-100 p-3 rounded mb-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <div>
                        <dt class="text-sm text-gray-500">Assunto do Ticket</dt>
                        <dd class="font-semibold">{{ $ticket->subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Número</dt>
                        <dd class="font-semibold">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Resposta de</dt>
                        <dd class="font-semibold">
                            @if(isset($reply->user) && $reply->user)
                                {{ $reply->user->name ?? ($reply->user->email ?? 'Usuário') }}
                            @elseif(isset($reply->contact) && $reply->contact)
                                {{ $reply->contact->name ?? ($reply->contact->email ?? 'Contato') }}
                            @else
                                Sistema
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Data da resposta</dt>
                        <dd class="font-semibold">{{ optional($reply->created_at)->toDayDateTimeString() ?? '' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-600 mb-2">Mensagem:</h2>
                <div class="bg-white border border-gray-200 p-4 rounded whitespace-pre-wrap text-gray-700">
{!! nl2br(e($reply->content)) !!}
                </div>
            </div>

            @if($reply->attachments && $reply->attachments->count() > 0)
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Anexos ({{ $reply->attachments->count() }}):</h3>
                <ul class="list-disc list-inside text-sm text-gray-600">
                    @foreach($reply->attachments as $attachment)
                    <li>
                        {{ $attachment->file_name }}
                        <span class="text-gray-400">({{ number_format($attachment->file_size / 1024, 2) }} KB)</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(!empty($reply->cc) && is_array($reply->cc) && count($reply->cc) > 0)
            <div class="mb-4 text-sm">
                <span class="text-gray-600">CC:</span>
                <span class="text-gray-700">{{ implode(', ', $reply->cc) }}</span>
            </div>
            @endif

            @php
                $url = rtrim(config('app.url', env('APP_URL', 'https://ticket-system.test/')), '/') . '/tickets/' . ($ticket->id ?? '');
            @endphp

            <p class="mb-4">
                <a class="inline-block bg-green-600 text-white px-4 py-2 rounded-md font-semibold no-underline" href="{{ $url }}">Ver ticket completo</a>
            </p>

            <p class="text-sm text-gray-500">Este é um e-mail automático. Para responder, acesse o ticket através do link acima.</p>
        </div>

        <div class="p-4 bg-white border-t border-gray-100 text-sm text-gray-500">
            Ticket System — {{ config('app.name', 'App') }}
        </div>
    </div>
</body>
</html>
