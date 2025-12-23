<?php

namespace App\Services;

use App\Models\NotificationTemplate;

class NotificationTemplateRenderer
{
    public function render(string $slug, array $data = []): ?array
    {
        $template = NotificationTemplate::query()
            ->where('slug', $slug)
            ->where('enabled', true)
            ->first();

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
