<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Ticket</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            line-height: 1.6;
        }
        .email-wrapper {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 40px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .email-content {
            padding: 50px 40px;
            background: #ffffff;
        }
        .greeting {
            color: #1a202c;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .ticket-details {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            font-weight: 600;
            color: #667eea;
            min-width: 120px;
            font-size: 14px;
        }
        .detail-value {
            color: #2d3748;
            flex: 1;
            font-size: 14px;
            word-break: break-word;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 10px 25px rgba(102,126,234,0.4);
        }
        .info-box {
            background: #e6f2ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .info-box p {
            color: #2d3748;
            font-size: 14px;
            margin: 8px 0;
        }
        .email-footer {
            background: #f7fafc;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer-text {
            color: #718096;
            font-size: 13px;
            margin: 10px 0;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 20px 10px; }
            .email-content { padding: 30px 20px; }
            .email-header { padding: 35px 20px; }
            .greeting { font-size: 20px; }
            .detail-row { flex-direction: column; }
            .detail-label { margin-bottom: 5px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Ticket {{ $ticket->ticket_number ?? 'N/A' }} criado:</h1>
            <p style="color: #e0e7ff; margin-top: 12px; font-size: 16px;">{{ $ticket->subject ?? 'Sem assunto' }}</p>
        </div>

        <div class="email-content">
            <div class="greeting">Olá,</div>

            <p style="color: #4a5568; font-size: 16px; margin-bottom: 25px;">
                Um novo ticket foi criado na inboxe <strong>{{ $ticket->inbox->name ?? 'N/A' }}</strong> e requer a sua atenção.
            </p>

            <div class="ticket-details">
                <div class="detail-row">
                    <div class="detail-label">Ticket:</div>
                    <div class="detail-value">{{ $ticket->ticket_number ?? 'N/A' }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Assunto:</div>
                    <div class="detail-value">{{ $ticket->subject ?? 'Sem assunto' }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Entidade:</div>
                    <div class="detail-value">{{ $ticket->entity->name ?? 'N/A' }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Requester:</div>
                    <div class="detail-value">{{ $ticket->requester->name ?? 'Desconhecido' }} ({{ $ticket->requester->email ?? 'N/A' }})</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Descrição:</div>
                    <div class="detail-value">{{ $ticket->content ?? 'Nenhuma descrição fornecida' }}</div>
                </div>
            </div>

            <div class="info-box">
                <p><strong>💡 Dica:</strong> Clique no botão abaixo para ver todos os detalhes do ticket e começar a trabalhar nele.</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $url }}" class="cta-button">Ver Ticket Completo</a>
            </div>

            <p style="color: #718096; font-size: 14px; text-align: center; margin-top: 40px;">
                Este é um email automático. Por favor, não responda diretamente a este email.
            </p>
        </div>

        <div class="email-footer">
            <p class="footer-text">
                <strong>{{ config('app.name', 'Sistema de Tickets') }}</strong><br>
                © {{ date('Y') }} Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
