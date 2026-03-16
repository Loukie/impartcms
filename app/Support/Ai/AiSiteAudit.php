<?php

namespace App\Support\Ai;

class AiSiteAudit
{
    /**
     * Audit and auto-fix generated HTML for premium clone requirements.
     *
     * @param string $html The generated page HTML
     * @param array $context Context: design_system, nav_logo_url, allowed_colors, etc.
     * @return array{html:string, issues:array<int,string>, fixes:array<int,string>}
     */
    public function auditAndFix(string $html, array $context = []): array
    {
        $issues = [];
        $fixes = [];
        $fixedHtml = $html;

        // 1. Enforce logo in nav (if missing, inject)
        if (!preg_match('/<nav[^>]*>.*<img[^>]*class=["\']ai-nav-logo["\'][^>]*>.*<\/nav>/is', $fixedHtml)) {
            $logo = $context['nav_logo_url'] ?? '';
            if ($logo) {
                // Insert logo into nav if missing
                $fixedHtml = preg_replace(
                    '/(<nav[^>]*>)(.*?)(<\/nav>)/is',
                    '$1<a class="ai-nav-logo-wrap" href="/"><img class="ai-nav-logo" src="' . htmlspecialchars($logo) . '" alt="Logo" /></a>$2$3',
                    $fixedHtml, 1, $count
                );
                if ($count > 0) {
                    $fixes[] = 'Injected missing logo into navigation.';
                } else {
                    $issues[] = 'Navigation bar found but could not inject logo.';
                }
            } else {
                $issues[] = 'No logo URL provided for nav injection.';
            }
        }

        // 2. Enforce only allowed colors (replace disallowed colors)
        $allowedColors = (array) ($context['allowed_colors'] ?? []);
        if ($allowedColors) {
            $fixedHtml = preg_replace_callback('/#[0-9a-fA-F]{3,6}/', function ($m) use ($allowedColors, &$fixes) {
                $color = strtolower($m[0]);
                if (!in_array($color, $allowedColors, true)) {
                    $fixes[] = 'Replaced disallowed color ' . $color . ' with ' . $allowedColors[0];
                    return $allowedColors[0];
                }
                return $color;
            }, $fixedHtml);
        }

        // 3. Enforce full-width layout (container max-width, section spacing)
        if (!preg_match('/max-width\s*:\s*100%/', $fixedHtml)) {
            $fixedHtml = preg_replace('/max-width\s*:\s*\d+px/', 'max-width:100%', $fixedHtml, -1, $count);
            if ($count > 0) {
                $fixes[] = 'Set container max-width to 100% for full-width layout.';
            }
        }

        // 4. Enforce nav state (transparent on home, solid on scroll/inner)
        // (Assume nav JS is present; just check nav classes)
        if (!preg_match('/class=["\"][^"\"]*ai-shared-nav[^"\"]*nav-transparent/', $fixedHtml)) {
            $fixedHtml = preg_replace('/class=(["\"][^"\"]*ai-shared-nav)/', 'class=$1 nav-transparent', $fixedHtml, 1, $count);
            if ($count > 0) {
                $fixes[] = 'Added nav-transparent class to navigation.';
            }
        }

        // 5. Add audit marker comment
        $fixedHtml = "<!-- Audited and auto-fixed by AiSiteAudit on " . date('Y-m-d') . " -->\n" . $fixedHtml;

        return [
            'html' => $fixedHtml,
            'issues' => $issues,
            'fixes' => $fixes,
        ];
    }
}
