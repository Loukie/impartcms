<?php

namespace App\Support\Ai;

class AiVisualRedesigner
{
    public function __construct(
        private readonly VisionClientInterface $vision,
        private readonly HtmlSanitiser $sanitiser,
    ) {}

    /**
     * Redesign a page based on a current screenshot + a reference screenshot.
     *
     * @return array{raw_html:string, clean_html:string, model?:string, meta?:array}
     */
    public function redesign(string $pageTitle, string $instruction, string $currentScreenshot, string $referenceScreenshot): array
    {
        $instructions = implode("\n", [
            'Output ONLY HTML. No markdown. No backticks. No commentary.',
            'Do NOT include <script> tags, inline JS, or event handler attributes (onclick, onload, etc.).',
            'No iframes, embeds, or external JS includes.',
            'All links must be http(s) or relative. No javascript: links.',
            'Return an HTML FRAGMENT only (no <html>, <head>, <body>, or <!doctype html>).',
            'Use clean semantic HTML and sensible class names (do not assume Tailwind is available unless you must).',
        ]);

        $input = implode("\n", [
            "Page title: {$pageTitle}",
            '',
            'Task:',
            '- You will be given 2 screenshots: (1) current page, (2) reference site page.',
            '- Analyse the layout + hierarchy of the reference and redesign the current page to match the vibe and clarity.',
            '- Keep the page content meaning consistent, but rewrite copy to be clearer if needed.',
            '',
            'User instruction:',
            $instruction,
            '',
            'Only output HTML.',
        ]);

        $res = $this->vision->generateTextWithImages(
            input: $input,
            instructions: $instructions,
            imagePaths: [$currentScreenshot, $referenceScreenshot],
        );

        $raw = (string) ($res['output_text'] ?? '');
        $clean = $this->sanitiser->clean($raw);

        return [
            'raw_html' => $raw,
            'clean_html' => $clean,
            'model' => $res['model'] ?? null,
            'meta' => $res['meta'] ?? null,
        ];
    }
}
