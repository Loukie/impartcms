<?php

namespace App\Support\Ai;

class AiPageGenerator
{
    public function __construct(
        private readonly LlmClientInterface $llm,
        private readonly HtmlSanitiser $sanitiser,
    ) {}

    /**
     * Generate safe HTML for a CMS page body.
     */
    public function generateHtml(string $brief, array $options = []): array
    {
        $title = trim((string) ($options['title'] ?? ''));
        $styleMode = (string) ($options['style_mode'] ?? 'inline'); // inline|classes
        $fullDocument = (bool) ($options['full_document'] ?? false);

        $instructions = $this->buildInstructions($styleMode, $fullDocument);
        $input = $this->buildInput($brief, $title, $styleMode, $fullDocument);

        $res = $this->llm->generateText($input, $instructions);
        $raw = (string) ($res['output_text'] ?? ($res['text'] ?? ''));
        $clean = $this->sanitiser->clean($raw);

        return [
            'raw_html' => $raw,
            'clean_html' => $clean,
            'model' => $res['model'] ?? null,
            'meta' => $res['meta'] ?? null,
        ];
    }

    private function buildInstructions(string $styleMode, bool $fullDocument): string
    {
        $styleMode = $styleMode === 'classes' ? 'classes' : 'inline';

        $rules = [
            'Output ONLY HTML. No markdown. No backticks.',
            'Do NOT include <script> tags, inline JS, or event handler attributes (onclick, onload, etc.).',
            'No iframes, embeds, or external JS includes.',
            'All links must be http(s) or relative. No javascript: links.',
            'Keep the structure clean and readable.',
            'Use accessible markup (labels for inputs, sensible heading hierarchy).',
        ];

        if ($fullDocument) {
            $rules[] = 'Return a FULL HTML document including <!doctype html>, <html>, <head>, and <body>. Use minimal inline CSS in a <style> tag if needed.';
        } else {
            $rules[] = 'Return an HTML FRAGMENT only (no <html>, <head>, <body>, or <!doctype html>).';
        }

        if ($styleMode === 'inline') {
            $rules[] = 'Prefer inline styles (style="...") and simple semantic HTML. Do not rely on Tailwind classes being available.';
        } else {
            $rules[] = 'You may use class attributes for styling. Keep classes sensible and minimal.';
        }

        return implode("\n", $rules);
    }

    private function buildInput(string $brief, string $title, string $styleMode, bool $fullDocument): string
    {
        $brief = trim($brief);
        $title = trim($title);

        $parts = [];

        if ($title !== '') {
            $parts[] = 'Page title: ' . $title;
        }

        $parts[] = 'Brief:';
        $parts[] = $brief;

        $parts[] = '';
        $parts[] = 'Content guidelines:';
        $parts[] = '- Use clear section headings.';
        $parts[] = '- Include a strong above-the-fold section.';
        $parts[] = '- Include at least 2 supporting sections (features, benefits, FAQ, testimonials, etc.) when appropriate.';
        $parts[] = '- Include a CTA section.';

        if (!$fullDocument) {
            $parts[] = '';
            $parts[] = 'IMPORTANT: This HTML will be injected into an existing Blade theme. Do not include the outer document tags.';
        }

        if ($styleMode !== 'inline') {
            $parts[] = '';
            $parts[] = 'Styling: you may use classes, but avoid depending on any specific framework unless the brief explicitly says so.';
        }

        // Prevent prompt injection from brief by explicitly delimiting it.
        $final = implode("\n", $parts);
        $final .= "\n\n---\nOnly output HTML.";

        return $final;
    }
}
