<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ticket criado - {{ $ticket->ticket_number ?? 'N/A' }}</title>
</head>
<body class="bg-gray-100 antialiased">
    <div class="max-w-3xl mx-auto my-6 bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-sky-400 text-white p-4">
            <h1 class="text-lg font-semibold">Ticket {{ $ticket->ticket_number ?? 'N/A' }} criado:</h1>
            <p class="text-sm mt-2 text-blue-100">{{ $ticket->subject }}</p>
        </div>

        <div class="p-6 text-gray-800">
            <p class="mb-2">Olá,</p>
            <p class="mb-4 text-gray-700">Um novo ticket foi criado no sistema. Abaixo estão os detalhes principais.</p>

            <div class="bg-gray-50 border border-gray-100 p-3 rounded mb-4">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                    <div>
                        <dt class="text-sm text-gray-500">Assunto</dt>
                        <dd class="font-semibold">{{ $ticket->subject }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Inbox</dt>
                        <dd class="font-semibold">{{ $ticket->inbox_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Número</dt>
                        <dd class="font-semibold">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Criado em</dt>
                        <dd class="font-semibold">{{ optional($ticket->created_at)->toDayDateTimeString() ?? '' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="content mb-4 whitespace-pre-wrap text-gray-700">{!! nl2br(e($ticket->content ?? '— Sem descrição —')) !!}</div>

            @php
                $url = rtrim(config('app.url', env('APP_URL', 'https://ticket-system.test/')), '/') . '/tickets/' . ($ticket->id ?? '');
            @endphp

            <p class="mb-4">
                <a class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md font-semibold no-underline" href="{{ $url }}">Ver ticket</a>
            </p>

            <p class="text-sm text-gray-500">Este é um e-mail automático, por favor não responda diretamente a ele.</p>
        </div>

        <div class="p-4 bg-white border-t border-gray-100 text-sm text-gray-500">
            Ticket System — {{ config('app.name', 'App') }}
        </div>
    </div>
</body>
</html>
