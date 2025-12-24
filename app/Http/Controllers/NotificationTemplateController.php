<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->currentUserOrFail();

        $query = NotificationTemplate::query()->orderBy('slug');
        if ($request->filled('inbox_id')) {
            $query->where('inbox_id', $request->inbox_id);
        }

        return Inertia::render('NotificationTemplates/Index', [
            'templates' => $query->get(),
            'filters' => [
                'inboxes' => Inbox::select('id', 'name')->get(),
            ],
            'queryParams' => [
                'inbox_id' => $request->inbox_id,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->currentUserOrFail();

                $defaultBodyHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{app.name}}</title>
    <style>
        body { margin:0; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #1f2937; }
        .wrapper { max-width: 640px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 18px 60px rgba(31,41,55,0.12); }
        .hero { background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 28px 32px; color: #fff; }
        .badge { display:inline-block; padding:6px 12px; border-radius:999px; background: rgba(255,255,255,0.15); font-size:12px; letter-spacing:0.08em; }
        h1 { margin: 12px 0 0; font-size: 26px; }
        .content { padding: 28px 32px 8px; line-height: 1.6; }
        .card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; margin:16px 0; }
        .muted { color:#6b7280; font-size: 14px; }
        .btn { display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:12px 18px; border-radius:10px; font-weight:600; }
        .footer { padding: 0 32px 28px; color:#9ca3af; font-size:13px; text-align:center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <span class="badge">{{app.name}}</span>
            <h1>Atualização do ticket {{ticket.number}}</h1>
            <p class="muted" style="color:rgba(255,255,255,0.8); margin-top:8px;">
                {{ticket.subject}}
            </p>
        </div>

        <div class="content">
            <p>Olá,</p>
            <p>Temos novidades sobre o seu ticket. Veja o resumo abaixo:</p>

            <div class="card">
                <strong>Ticket:</strong> {{ticket.number}}<br />
                <strong>Assunto:</strong> {{ticket.subject}}<br />
                <strong>Mensagem:</strong> {{message.content}}
            </div>

            <p class="muted">Gerado em {{ticket.created_at}}</p>

            <p style="margin: 20px 0;">
                <a class="btn" href="{{ticket.url}}">Ver detalhes do ticket</a>
            </p>

            <p>Se precisar de ajuda, basta responder a este e-mail.</p>
        </div>

        <div class="footer">
            © {{app.name}} — {{ticket.number}}
        </div>
    </div>
</body>
</html>
HTML;

                return Inertia::render('NotificationTemplates/Create', [
                        'inboxes' => Inbox::select('id', 'name')->get(),
                        'defaultBodyHtml' => $defaultBodyHtml,
                        'slug' => $request->query('slug'),
                        'subject' => $request->query('subject'),
                        'body_html' => $request->query('body_html'),
                        'locale' => $request->query('locale'),
                ]);
    }

    public function store(Request $request)
    {
        $this->currentUserOrFail();

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'locale' => ['nullable', 'string', 'max:10'],
            'inbox_id' => ['nullable', 'exists:inboxes,id'],
            'enabled' => ['boolean'],
        ]);

        $data['enabled'] = $data['enabled'] ?? true;

        $created = NotificationTemplate::create($data);
        $created->enabled = true;
        $created->save();
        $this->deactivatePeers($created);

        return redirect()->route('notification-templates.index')
            ->with('success', 'Template criado e ativado!');
    }

    public function edit(NotificationTemplate $template)
    {
        $this->currentUserOrFail();

        return Inertia::render('NotificationTemplates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $this->currentUserOrFail();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'enabled' => ['boolean'],
        ]);

        $template->update($data);

        return redirect()->route('notification-templates.index')
            ->with('success', 'Template atualizado com sucesso!');
    }

    public function activate(NotificationTemplate $template)
    {
        $this->currentUserOrFail();

        $query = NotificationTemplate::where('slug', $template->slug);
        if (is_null($template->inbox_id)) {
            $query->whereNull('inbox_id');
        } else {
            $query->where('inbox_id', $template->inbox_id);
        }

        $query->where('id', '!=', $template->id)->update(['enabled' => false]);

        $template->enabled = true;
        $template->save();

        return redirect()->route('notification-templates.index')
            ->with('success', 'Template ativado para envio!');
    }

    public function preview(Request $request, NotificationTemplate $template)
    {
        $this->currentUserOrFail();

        $subject = $request->input('subject', $template->subject);
        $bodyHtml = $request->input('body_html', $template->body_html);

        $sampleData = [
            'ticket' => [
                'number' => 'TKT-12345',
                'subject' => 'Exemplo de ticket para preview',
                'content' => 'Esta é uma descrição de exemplo para visualizar o template.',
                'created_at' => now()->toDayDateTimeString(),
                'url' => url('/tickets/1'),
            ],
            'message' => [
                'content' => 'Esta é uma resposta de exemplo para visualizar o template de reply.',
                'created_at' => now()->toDayDateTimeString(),
            ],
            'app' => [
                'name' => config('app.name', 'Ticket System'),
            ],
        ];

        $flat = $this->dotFlatten($sampleData);
        $search = [];
        $replace = [];
        foreach ($flat as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = (string) $value;
        }

        return response()->json([
            'subject' => str_replace($search, $replace, $subject),
            'html' => str_replace($search, $replace, $bodyHtml),
        ]);
    }

    private function deactivatePeers(NotificationTemplate $template): void
    {
        $query = NotificationTemplate::where('slug', $template->slug)
            ->where('id', '!=', $template->id);

        if (is_null($template->inbox_id)) {
            $query->whereNull('inbox_id');
        } else {
            $query->where('inbox_id', $template->inbox_id);
        }

        $query->update(['enabled' => false]);
    }

    private function dotFlatten(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result += $this->dotFlatten($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    private function currentUserOrFail()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Autenticação requerida');
        }

        return $user;
    }
}
