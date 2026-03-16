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

        // 1. Enforce reference nav logo (never allow fallback, white box, or broken image)
        $logo = $context['nav_logo_url'] ?? '';
        if (!$logo || stripos($logo, 'placeholder') !== false || stripos($logo, 'svg') !== false || stripos($logo, 'white') !== false) {
            $issues[] = 'Reference nav logo missing, fallback, or broken. HALT: Manual review required.';
            return [
                'html' => $fixedHtml,
                'issues' => $issues,
                'fixes' => $fixes,
            ];
        }
        // Remove any existing nav logo and inject the correct one
        $fixedHtml = preg_replace_callback('/<nav[^>]*>.*?<\/nav>/is', function($m) use ($logo, &$fixes) {
            $nav = $m[0];
            // Remove any <img> or <svg> with class/id/logo/brand
            $nav = preg_replace('/<img[^>]*(logo|brand)[^>]*>/i', '', $nav);
            $nav = preg_replace('/<svg[^>]*(logo|brand)[^>]*>[\s\S]*?<\/svg>/i', '', $nav);
            // Inject reference logo at start
            $nav = preg_replace('/(<nav[^>]*>)/i', '$1<a class="ai-nav-logo-wrap" href="/"><img class="ai-nav-logo" src="' . htmlspecialchars($logo) . '" alt="Logo" /></a>', $nav, 1, $count);
            if ($count > 0) {
                $fixes[] = 'Injected strict reference nav logo.';
            }
            return $nav;
        }, $fixedHtml, 1);

        // 2. Remove non-reference overlays/backgrounds (gray, non-palette)
        $allowedColors = (array) ($context['allowed_colors'] ?? []);
        if ($allowedColors) {
            $fixedHtml = preg_replace_callback('/#[0-9a-fA-F]{3,6}/', function ($m) use ($allowedColors, &$fixes) {
                $color = strtolower($m[0]);
                if (!in_array($color, $allowedColors, true)) {
                    $fixes[] = 'Removed/replaced non-reference color ' . $color . ' with ' . $allowedColors[0];
                    return $allowedColors[0];
                }
                return $color;
            }, $fixedHtml);
            // Remove gray overlays (rgba/hex grays not in palette)
            $fixedHtml = preg_replace('/background\s*:\s*(rgba?\([^;]*[0-9]{1,3},\s*[0-9]{1,3},\s*[0-9]{1,3}(,\s*[0-9\.]+)?\)|#888888|#666666|#444444|#222222|#333333|#555555)[^;]*;?/i', '', $fixedHtml, -1, $grayCount);
            if (!empty($grayCount)) {
                $fixes[] = 'Removed non-reference gray overlays/backgrounds.';
            }
        }

        // 3. Enforce section rhythm and spacing (no collapsed/merged sections)
        // Add margin between sections if missing
        $fixedHtml = preg_replace('/(<\/section>)(\s*)(<section)/i', '$1\n<div style="height:48px"></div>\n$3', $fixedHtml, -1, $sectionCount);
        if ($sectionCount > 0) {
            $fixes[] = 'Inserted spacing between sections to enforce rhythm.';
        }

        // 4. Enforce full-width layout (container max-width, section spacing)
        if (!preg_match('/max-width\s*:\s*100%/', $fixedHtml)) {
            $fixedHtml = preg_replace('/max-width\s*:\s*\d+px/', 'max-width:100%', $fixedHtml, -1, $count);
            if ($count > 0) {
                $fixes[] = 'Set container max-width to 100% for full-width layout.';
            }
        }

        // 5. Enforce nav state (transparent on home, solid on scroll/inner)
        if (!preg_match('/class=["\"][^"\"]*ai-shared-nav[^"\"]*nav-transparent/', $fixedHtml)) {
            $fixedHtml = preg_replace('/class=(["\"][^"\"]*ai-shared-nav)/', 'class=$1 nav-transparent', $fixedHtml, 1, $count);
            if ($count > 0) {
                $fixes[] = 'Added nav-transparent class to navigation.';
            }
        }

        // 6. Add audit marker comment
        $fixedHtml = "<!-- Audited and auto-fixed by AiSiteAudit on " . date('Y-m-d') . " -->\n" . $fixedHtml;

        return [
            'html' => $fixedHtml,
            'issues' => $issues,
            'fixes' => $fixes,
        ];
    }

// ...existing code...
}
