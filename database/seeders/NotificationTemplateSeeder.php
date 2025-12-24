<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
  public function run(): void
  {
    $templates = [
      [
        'slug' => 'ticket_created',
        'subject' => 'Ticket {{ticket.number}} criado: {{ticket.subject}}',
        'body_html' => $this->createdTemplate(),
        'enabled' => true,
      ],
      [
        'slug' => 'ticket_replied',
        'subject' => 'Nova resposta no ticket {{ticket.number}}',
        'body_html' => $this->repliedTemplate(),
        'enabled' => true,
      ],
      [
        'slug' => 'ticket_created_operator',
        'subject' => '🎫 Novo Ticket: {{ticket.subject}}',
        'body_html' => $this->createdOperatorTemplate(),
        'enabled' => true,
      ],
      [
        'slug' => 'ticket_replied_operator',
        'subject' => '💬 Nova resposta no ticket {{ticket.number}}',
        'body_html' => $this->repliedOperatorTemplate(),
        'enabled' => true,
      ],
    ];

    foreach ($templates as $template) {
      NotificationTemplate::updateOrCreate(
        ['slug' => $template['slug']],
        [
          'subject' => $template['subject'],
          'body_html' => $template['body_html'],
          'enabled' => $template['enabled'],
        ]
      );
    }
  }

  private function createdTemplate(): string
  {
    return <<<HTML
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ticket criado</title>
  <style>body{margin:0;padding:0;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;background:#f8fafc;color:#0f172a;}</style>
</head>
<body>
  <div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.08);">
    <div style="background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:#fff;padding:16px 20px;font-weight:700;font-size:16px;display:flex;align-items:center;gap:10px;">
      <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,0.15);">🎫</span>
      Ticket criado — {{ticket.number}}
    </div>

    <div style="padding:20px 22px;">
      <p style="margin:0 0 12px 0;">Olá,</p>
      <p style="margin:0 0 16px 0;color:#334155;">Um novo ticket foi criado. Aqui estão os detalhes principais:</p>

      <div style="border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:14px 16px;margin-bottom:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;font-size:14px;">
          <div>
            <div style="color:#64748b;font-weight:600;font-size:12px;">Assunto</div>
            <div style="font-weight:700;">{{ticket.subject}}</div>
          </div>
          <div>
            <div style="color:#64748b;font-weight:600;font-size:12px;">Número</div>
            <div style="font-weight:700;">{{ticket.number}}</div>
          </div>
          <div>
            <div style="color:#64748b;font-weight:600;font-size:12px;">Criado em</div>
            <div style="font-weight:600;">{{ticket.created_at}}</div>
          </div>
        </div>
      </div>

      <div style="margin-bottom:18px;">
        <div style="color:#64748b;font-weight:600;font-size:12px;margin-bottom:6px;">Descrição</div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;font-size:14px;line-height:1.6;">{{ticket.content}}</div>
      </div>

      <a href="{{ticket.url}}" style="display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">Ver ticket</a>
      <p style="margin-top:14px;color:#94a3b8;font-size:12px;">Este é um e-mail automático. Não responda diretamente.</p>
    </div>

    <div style="border-top:1px solid #e2e8f0;padding:14px 18px;font-size:12px;color:#94a3b8;">
      Ticket System • {{app.name}}
    </div>
  </div>
</body>
</html>
HTML;
  }

  private function repliedTemplate(): string
  {
    return <<<HTML
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Nova resposta</title>
  <style>body{margin:0;padding:0;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;background:#f8fafc;color:#0f172a;}</style>
</head>
<body>
  <div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.08);">
    <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:16px 20px;font-weight:700;font-size:16px;display:flex;align-items:center;gap:10px;">
      <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,0.15);">💬</span>
      Nova resposta — {{ticket.number}}
    </div>

    <div style="padding:20px 22px;">
      <p style="margin:0 0 12px 0;">Olá,</p>
      <p style="margin:0 0 14px 0;color:#334155;">Há uma nova resposta no ticket <strong>{{ticket.number}}</strong>.</p>

      <div style="border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:14px 16px;margin-bottom:16px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;font-size:14px;">
          <div>
            <div style="color:#64748b;font-weight:600;font-size:12px;">Assunto</div>
            <div style="font-weight:700;">{{ticket.subject}}</div>
          </div>
          <div>
            <div style="color:#64748b;font-weight:600;font-size:12px;">Data</div>
            <div style="font-weight:600;">{{message.created_at}}</div>
          </div>
        </div>
      </div>

      <div style="margin-bottom:18px;">
        <div style="color:#64748b;font-weight:600;font-size:12px;margin-bottom:6px;">Mensagem</div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;font-size:14px;line-height:1.6;">{{message.content}}</div>
      </div>

      <a href="{{ticket.url}}" style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">Ver ticket</a>
      <p style="margin-top:14px;color:#94a3b8;font-size:12px;">Este é um e-mail automático. Não responda diretamente.</p>
    </div>

    <div style="border-top:1px solid #e2e8f0;padding:14px 18px;font-size:12px;color:#94a3b8;">
      Ticket System • {{app.name}}
    </div>
  </div>
</body>
</html>
HTML;
  }

  private function createdOperatorTemplate(): string
  {
    return <<<'HTML'
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{app.name}}</title>
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
            position: relative;
        }
        .email-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\'100\' height=\'100\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 0h100v100H0z\' fill=\'none\'/%3E%3Cpath d=\'M0 0l50 50M50 0l50 50M0 50l50 50M50 50l50 50\' stroke=\'%23ffffff\' stroke-opacity=\'0.05\' stroke-width=\'2\'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        .logo {
            position: relative;
            z-index: 1;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            margin-bottom: 15px;
        }
        .email-header h1 {
            position: relative;
            z-index: 1;
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
            min-width: 140px;
            font-size: 14px;
        }
        .detail-value {
            color: #2d3748;
            flex: 1;
            font-size: 14px;
            word-break: break-word;
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
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 40px 0;
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
            <div class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17l-5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>🎫 Novo Ticket</h1>
        </div>

        <div class="email-content">
            <div class="greeting">Olá,</div>

            <p style="color: #4a5568; font-size: 16px; margin-bottom: 25px;">
                Um novo ticket foi criado na inboxe <strong>{{ticket.inbox_name}}</strong> e requer a sua atenção.
            </p>

            <div class="ticket-details">
                <div class="detail-row">
                    <div class="detail-label">📋 Ticket:</div>
                    <div class="detail-value">{{ticket.number}}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">📝 Assunto:</div>
                    <div class="detail-value">{{ticket.subject}}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">🏢 Entidade:</div>
                    <div class="detail-value">{{ticket.entity_name}}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">👤 Requester:</div>
                    <div class="detail-value">{{ticket.requester_name}} ({{ticket.requester_email}})</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">📄 Descrição:</div>
                    <div class="detail-value">{{ticket.content}}</div>
                </div>
            </div>

            <div class="info-box">
                <p><strong>💡 Dica:</strong> Clique no botão abaixo para ver todos os detalhes do ticket e começar a trabalhar nele. Atribua-o a si ou a outro operador para garantir que é processado.</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ticket.url}}" class="cta-button">Abrir Ticket no Sistema</a>
            </div>

            <div class="divider"></div>

            <p style="color: #718096; font-size: 14px; text-align: center;">
                Este é um email automático enviado pelo sistema de gestão de tickets. Por favor, não responda diretamente a este email.
            </p>
        </div>

        <div class="email-footer">
            <p class="footer-text">
                <strong>{{app.name}}</strong><br>
                © 2025 Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
  }

  private function repliedOperatorTemplate(): string
  {
    return <<<'HTML'
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{app.name}}</title>
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
            position: relative;
        }
        .email-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\'100\' height=\'100\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 0h100v100H0z\' fill=\'none\'/%3E%3Cpath d=\'M0 0l50 50M50 0l50 50M0 50l50 50M50 50l50 50\' stroke=\'%23ffffff\' stroke-opacity=\'0.05\' stroke-width=\'2\'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        .logo {
            position: relative;
            z-index: 1;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            margin-bottom: 15px;
        }
        .email-header h1 {
            position: relative;
            z-index: 1;
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
        .ticket-header {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .ticket-number {
            font-size: 12px;
            color: #667eea;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ticket-subject {
            font-size: 18px;
            color: #1a202c;
            font-weight: 700;
            margin-top: 5px;
        }
        .message-preview {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #764ba2;
        }
        .message-author {
            font-size: 13px;
            font-weight: 600;
            color: #764ba2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .message-content {
            color: #4a5568;
            font-size: 15px;
            line-height: 1.7;
            word-break: break-word;
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
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 40px 0;
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
            .ticket-subject { font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <div class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17l-5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>💬 Nova Resposta</h1>
        </div>

        <div class="email-content">
            <div class="greeting">Olá,</div>

            <p style="color: #4a5568; font-size: 16px; margin-bottom: 25px;">
                Uma nova resposta foi adicionada a um ticket sob a sua supervisão.
            </p>

            <div class="ticket-header">
                <div class="ticket-number">{{ticket.number}}</div>
                <div class="ticket-subject">{{ticket.subject}}</div>
            </div>

            <div class="message-preview">
                <div class="message-author">📝 {{message.author_name}} ({{message.author_email}})</div>
                <div class="message-content">{{message.content}}</div>
            </div>

            <div class="info-box">
                <p><strong>💡 Dica:</strong> Revise a resposta, atribua a si se necessário, e confirme que o ticket está sendo processado corretamente.</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ticket.url}}" class="cta-button">Ver Ticket Completo</a>
            </div>

            <div class="divider"></div>

            <p style="color: #718096; font-size: 14px; text-align: center;">
                Este é um email automático enviado pelo sistema de gestão de tickets.
            </p>
        </div>

        <div class="email-footer">
            <p class="footer-text">
                <strong>{{app.name}}</strong><br>
                © 2025 Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
  }
}
