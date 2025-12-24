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
