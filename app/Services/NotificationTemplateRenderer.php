<?php

namespace App\Services;

use App\Models\NotificationTemplate;

class NotificationTemplateRenderer
{
    /**
     * Render a template, preferring an inbox-specific version when provided.
     * If no inbox-specific template exists, falls back to the global template.
     */
    public function renderForInbox(string $slug, ?int $inboxId, array $data = []): ?array
    {
        $query = NotificationTemplate::query()
            ->where('slug', $slug)
            ->where('enabled', true);

        // Prefer inbox-specific template if inboxId is provided
        if ($inboxId) {
            $template = (clone $query)->where('inbox_id', $inboxId)->first();
            if (!$template) {
                // Fallback to global template (inbox_id = null)
                $template = (clone $query)->whereNull('inbox_id')->first();
            }
        } else {
            // No inbox provided: use global template
            $template = (clone $query)->whereNull('inbox_id')->first();
            // Or any enabled slug if global missing
            if (!$template) {
                $template = (clone $query)->first();
            }
        }

        if (!$template) {
            return null;
        }

        $flat = $this->dotFlatten($data);

        $search = [];
        $replace = [];
        foreach ($flat as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = (string) $value;
        }

        return [
            'subject' => str_replace($search, $replace, $template->subject),
            'html' => str_replace($search, $replace, $template->body_html),
        ];
    }

    /**
     * Backward-compatible render without inbox context.
     */
    public function render(string $slug, array $data = []): ?array
    {
        return $this->renderForInbox($slug, null, $data);
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
