<?php

namespace App\Support\Ai;

class LinkRewriter
{
    /**
     * Rewrite HTML links to match new page slugs in the CMS.
     *
     * @param string $html The HTML content to rewrite
     * @param array<string, string> $urlMap Original URL -> New slug mapping
     * @return string Rewritten HTML
     */
    public static function rewrite(string $html, array $urlMap): string
    {
        if (count($urlMap) === 0) {
            return $html;
        }

        // Also create relative path mappings
        $pathMap = [];
        foreach ($urlMap as $originalUrl => $newSlug) {
            $path = parse_url($originalUrl, PHP_URL_PATH) ?? '/';
            $path = rtrim($path, '/') ?: '/';
            $pathMap[$path] = '/' . trim($newSlug, '/');
        }

        $html = preg_replace_callback(
            '/href=(["\'])([^"\']+)\1/i',
            function ($matches) use ($urlMap, $pathMap) {
                $quote = $matches[1];
                $originalHref = $matches[2];

                // Skip non-HTTP(S) links, anchors, mailto, tel
                if (empty($originalHref) || 
                    str_starts_with($originalHref, '#') ||
                    str_starts_with($originalHref, 'javascript:') ||
                    str_starts_with($originalHref, 'mailto:') ||
                    str_starts_with($originalHref, 'tel:')) {
                    return $matches[0];
                }

                // Try direct URL mapping first
                if (isset($urlMap[$originalHref])) {
                    return 'href=' . $quote . '/' . trim($urlMap[$originalHref], '/') . $quote;
                }

                // Try path mapping
                $path = parse_url($originalHref, PHP_URL_PATH) ?? $originalHref;
                $path = rtrim($path, '/') ?: '/';
                if (isset($pathMap[$path])) {
                    return 'href=' . $quote . $pathMap[$path] . $quote;
                }

                // Return original if no mapping found
                return $matches[0];
            },
            $html
        );

        return $html;
    }
}
