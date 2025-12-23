<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::orderBy('slug')->get();

        return Inertia::render('NotificationTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function edit(NotificationTemplate $template)
    {
        return Inertia::render('NotificationTemplates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, NotificationTemplate $template)
    {
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
