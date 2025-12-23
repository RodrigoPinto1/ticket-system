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
}
