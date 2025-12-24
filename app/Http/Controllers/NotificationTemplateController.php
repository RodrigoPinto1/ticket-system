<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        // Optional filter by inbox; null means global templates
        $query = NotificationTemplate::query()->orderBy('slug');
        if ($request->filled('inbox_id')) {
            $query->where('inbox_id', $request->inbox_id);
        }
        $templates = $query->get();

        // Provide inboxes for filtering in the UI
        $inboxes = \App\Models\Inbox::select('id', 'name')->get();

        return Inertia::render('NotificationTemplates/Index', [
            'templates' => $templates,
            'filters' => [
                'inboxes' => $inboxes,
            ],
            'queryParams' => [
                'inbox_id' => $request->inbox_id,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        $inboxes = \App\Models\Inbox::select('id', 'name')->get();

        // Default beautiful template
        $defaultBodyHtml = '<!DOCTYPE html>
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
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .highlight-box {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecf2 100%);
            border-left: 4px solid #667eea;
            padding: 25px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .highlight-box h3 {
            color: #667eea;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .highlight-box p {
            color: #4a5568;
            margin: 8px 0;
            font-size: 15px;
        }
        .ticket-info {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        .ticket-info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .ticket-info-row:last-child { border-bottom: none; }
        .ticket-info-label {
            font-weight: 600;
            color: #667eea;
            min-width: 120px;
            font-size: 14px;
        }
        .ticket-info-value {
            color: #2d3748;
            flex: 1;
            font-size: 14px;
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
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102,126,234,0.5);
        }
        .message-preview {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid #764ba2;
        }
        .message-preview-label {
            font-size: 13px;
            font-weight: 600;
            color: #764ba2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .message-preview-text {
            color: #4a5568;
            font-size: 15px;
            line-height: 1.7;
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
        .footer-links {
            margin-top: 20px;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 13px;
            font-weight: 600;
        }
        @media only screen and (max-width: 600px) {
            body { padding: 20px 10px; }
            .email-content { padding: 30px 20px; }
            .email-header { padding: 35px 20px; }
            .greeting { font-size: 24px; }
            .ticket-info-row { flex-direction: column; }
            .ticket-info-label { margin-bottom: 5px; }
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
            <h1>{{app.name}}</h1>
        </div>

        <div class="email-content">
            <div class="greeting">Olá! 👋</div>

            <p style="color: #4a5568; font-size: 16px; margin-bottom: 25px;">
                Tem uma nova atualização sobre o seu ticket. Veja os detalhes abaixo:
            </p>

            <div class="highlight-box">
                <h3>📌 Detalhes do Ticket</h3>
                <p><strong>Número:</strong> {{ticket.number}}</p>
                <p><strong>Assunto:</strong> {{ticket.subject}}</p>
            </div>

            <div class="ticket-info">
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Ticket:</div>
                    <div class="ticket-info-value">{{ticket.number}}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Assunto:</div>
                    <div class="ticket-info-value">{{ticket.subject}}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Conteúdo:</div>
                    <div class="ticket-info-value">{{ticket.content}}</div>
                </div>
            </div>

            <div class="message-preview">
                <div class="message-preview-label">💬 Nova Mensagem</div>
                <div class="message-preview-text">{{message.content}}</div>
            </div>

            <div style="text-align: center;">
                <a href="{{ticket.url}}" class="cta-button">Ver Ticket Completo</a>
            </div>

            <div class="divider"></div>

            <p style="color: #718096; font-size: 14px; text-align: center;">
                Precisa de ajuda? Responda a este e-mail ou contacte o nosso suporte.<br>
                Estamos aqui para ajudar! 💙
            </p>
        </div>

        <div class="email-footer">
            <p class="footer-text">
                <strong>{{app.name}}</strong><br>
                © ' . date('Y') . ' Todos os direitos reservados.
            </p>
            <div class="footer-links">
                <a href="#">Política de Privacidade</a>
                <a href="#">Termos de Serviço</a>
                <a href="#">Contacto</a>
            </div>
        </div>
    </div>
</body>
</html>';

        return Inertia::render('NotificationTemplates/Create', [
            'inboxes' => $inboxes,
            'defaultBodyHtml' => $defaultBodyHtml,
            'slug' => $request->query('slug'),
            'subject' => $request->query('subject'),
            'body_html' => $request->query('body_html'),
            'locale' => $request->query('locale'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'locale' => ['nullable', 'string', 'max:10'],
            'inbox_id' => ['nullable', 'exists:inboxes,id'],
            'enabled' => ['boolean'],
        ]);

        // Ensure enabled has a default value
        if (!array_key_exists('enabled', $data)) {
            $data['enabled'] = true;
        }

        // If a template with the same slug already exists, update it instead of creating
        // Always create a new template and set it active, deactivating peers
        $created = NotificationTemplate::create($data);
        $created->enabled = true;
        $created->save();
        $this->deactivatePeers($created);

        return redirect()->route('notification-templates.index')
            ->with('success', 'Template criado e ativado!');
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

    public function edit(NotificationTemplate $template)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->where('role', 'operator')->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        return Inertia::render('NotificationTemplates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->where('role', 'operator')->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

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
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        // Deactivate other templates with the same slug and same inbox scope
        $query = NotificationTemplate::where('slug', $template->slug);
        if (is_null($template->inbox_id)) {
            $query->whereNull('inbox_id');
        } else {
            $query->where('inbox_id', $template->inbox_id);
        }
        $query->where('id', '!=', $template->id)->update(['enabled' => false]);

        // Activate selected template
        $template->enabled = true;
        $template->save();

        return redirect()->route('notification-templates.index')
            ->with('success', 'Template ativado para envio!');
    }

    public function preview(Request $request, NotificationTemplate $template)
    {
        $user = Auth::user();
        $isOperator = $user && $user->inboxRoles()->where('role', 'operator')->exists();
        if (!$isOperator) {
            abort(403, 'Acesso restrito a operadores');
        }

        $renderer = app(NotificationTemplateRenderer::class);

        // Use form data if provided, otherwise use template from DB
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

        // Manually do the replacement with current form data
        $flat = $this->dotFlatten($sampleData);
        $search = [];
        $replace = [];
        foreach ($flat as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = (string) $value;
        }

        $renderedSubject = str_replace($search, $replace, $subject);
        $renderedHtml = str_replace($search, $replace, $bodyHtml);

        return response()->json([
            'subject' => $renderedSubject,
            'html' => $renderedHtml,
        ]);
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
}
