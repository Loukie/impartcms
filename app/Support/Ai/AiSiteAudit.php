<?php

namespace App\Support\Ai;

class AiSiteAudit
{
    /**
     * Audit and auto-fix generated HTML for clone requirements.
     *
     * Intentionally minimal — only fixes things that are genuinely broken.
     * Aggressive colour/spacing fixes were removed because they caused more
     * harm than good (flattened tints, destroyed hover states, broke layouts).
     *
     * @param string $html The generated page HTML
     * @param array $context Context: nav_logo_url, allowed_colors
     * @return array{html:string, issues:array<int,string>, fixes:array<int,string>}
     */
    public function auditAndFix(string $html, array $context = []): array
    {
        $issues  = [];
        $fixes   = [];
        $fixedHtml = $html;

        // 1. Nav logo — update the src of the existing ai-nav-logo image if the
        //    reference logo is available and is not already in the HTML.
        $logo = trim((string) ($context['nav_logo_url'] ?? ''));
        if ($logo !== '' && stripos($logo, 'placeholder') === false) {
            if (stripos($fixedHtml, $logo) === false) {
                // Update the src on any existing ai-nav-logo img inside the shared nav
                $fixedHtml = preg_replace_callback(
                    '/<nav\b[^>]*class="[^"]*ai-shared-nav[^"]*"[^>]*>.*?<\/nav>/is',
                    function (array $m) use ($logo, &$fixes): string {
                        $nav = $m[0];
                        // If there's already an ai-nav-logo img, update its src
                        if (preg_match('/<img[^>]*class="[^"]*ai-nav-logo[^"]*"[^>]*>/i', $nav)) {
                            $nav = preg_replace(
                                '/(<img\b[^>]*class="[^"]*ai-nav-logo[^"]*"[^>]*)src="[^"]*"/i',
                                '$1src="' . htmlspecialchars($logo, ENT_QUOTES) . '"',
                                $nav
                            ) ?? $nav;
                            $fixes[] = 'Updated ai-nav-logo src to reference image.';
                        }
                        return $nav;
                    },
                    $fixedHtml,
                    1
                ) ?? $fixedHtml;
            }
        } else {
            $issues[] = 'No reference nav logo — nav will display text brand name.';
        }

        // 2. Ensure the AI shared nav has the nav-transparent class for scroll behaviour.
        //    The nav JS toggles between nav-transparent and nav-solid on scroll.
        if (
            preg_match('/class="[^"]*ai-shared-nav[^"]*"/i', $fixedHtml) &&
            !preg_match('/\bnav-transparent\b/i', $fixedHtml)
        ) {
            $fixedHtml = preg_replace(
                '/class="([^"]*\bai-shared-nav\b[^"]*)"/',
                'class="$1 nav-transparent"',
                $fixedHtml,
                1,
                $count
            ) ?? $fixedHtml;
            if ($count) {
                $fixes[] = 'Added nav-transparent class for scroll behaviour.';
            }
        }

        // 3. Audit marker
        $fixedHtml = '<!-- AiSiteAudit: ' . date('Y-m-d') . " -->\n" . $fixedHtml;

        return [
            'html'   => $fixedHtml,
            'issues' => $issues,
            'fixes'  => $fixes,
        ];
    }
}
