<?php

namespace App\Support;

/**
 * IconRenderer
 *
 * Renders a stored icon JSON (from the Media > Icons picker) into safe HTML.
 * - Font Awesome: prefers embedded SVG payload (portable). Falls back to <i class='...'>.
 * - Lucide: renders a <i data-lucide='...'> placeholder; lucide JS will hydrate it.
 */
final class IconRenderer
{
    /**
     * Render icon HTML.
     *
     * @param  string|null  $iconJson
     * @param  int|null     $sizeOverridePx  If provided, overrides stored size.
     * @param  string|null  $colourOverride  If provided, overrides stored colour.
     */
    public static function renderHtml(?string $iconJson, ?int $sizeOverridePx = null, ?string $colourOverride = null): string
    {
        $icon = self::decode($iconJson);
        if (!$icon) return '';

        $kind = $icon['kind'];
        $size = self::clampInt($sizeOverridePx ?? (int) ($icon['size'] ?? 24), 8, 256);
        $colour = self::normaliseColour($colourOverride ?? (string) ($icon['colour'] ?? '#111827'));

        if ($kind === 'fa') {
            // Prefer embedded SVG for portability.
            $svg = isset($icon['svg']) && is_string($icon['svg']) ? trim($icon['svg']) : '';
            if ($svg !== '' && str_starts_with($svg, '<svg')) {
                $svg = self::sanitizeSvg($svg);
                if ($svg !== '') {
                    return self::applySvgPresentation($svg, $size, $colour);
                }
            }

            // Fallback: CSS-based FA class.
            $cls = isset($icon['value']) ? (string) $icon['value'] : '';
            $cls = self::sanitizeFaClass($cls);
            if ($cls === '') return '';

            $style = "font-size:{$size}px;color:{$colour};line-height:1;display:inline-block";
            return "<i class='" . e($cls) . "' style='" . e($style) . "' aria-hidden='true'></i>";
        }

        if ($kind === 'lucide') {
            $name = isset($icon['value']) ? (string) $icon['value'] : '';
            $name = self::sanitizeLucideName($name);
            if ($name === '') return '';

            $style = "width:{$size}px;height:{$size}px;color:{$colour};display:inline-block";
            return "<i data-lucide='" . e($name) . "' style='" . e($style) . "' aria-hidden='true'></i>";
        }

        return '';
    }

    /**
     * Render ONLY an SVG string suitable for use as a favicon.
     * Returns empty string if not renderable as SVG.
     */
    public static function renderSvgString(?string $iconJson, int $sizePx = 64, string $colour = '#111827'): string
    {
        $icon = self::decode($iconJson);
        if (!$icon) return '';

        if (($icon['kind'] ?? '') !== 'fa') {
            // Currently only FA provides embedded SVG payloads.
            return '';
        }

        $svg = isset($icon['svg']) && is_string($icon['svg']) ? trim($icon['svg']) : '';
        if ($svg === '' || !str_starts_with($svg, '<svg')) return '';

        $svg = self::sanitizeSvg($svg);
        if ($svg === '') return '';

        $size = self::clampInt($sizePx, 8, 512);
        $colour = self::normaliseColour($colour);

        return self::applySvgPresentation($svg, $size, $colour);
    }

    // -----------------
    // Internals
    // -----------------

    private static function decode(?string $json): ?array
    {
        $raw = trim((string) ($json ?? ''));
        if ($raw === '') return null;

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) return null;
            $kind = strtolower((string) ($decoded['kind'] ?? ''));
            if (!in_array($kind, ['fa', 'lucide'], true)) return null;
            $decoded['kind'] = $kind;
            return $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function clampInt(int $v, int $min, int $max): int
    {
        if ($v < $min) return $min;
        if ($v > $max) return $max;
        return $v;
    }

    private static function normaliseColour(string $c): string
    {
        $c = trim($c);
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $c)) {
            return '#111827';
        }
        return $c;
    }

    private static function sanitizeFaClass(string $cls): string
    {
        $cls = trim(preg_replace('/\s+/', ' ', $cls));
        // Allow typical FA class patterns: fa-solid fa-house, etc.
        if ($cls === '' || !preg_match('/^[a-z0-9\s\-]+$/i', $cls)) return '';
        return $cls;
    }

    private static function sanitizeLucideName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || !preg_match('/^[a-z0-9\-]+$/', $name)) return '';
        return $name;
    }

    /**
     * Very small SVG sanitiser:
     * - strips <script> tags
     * - strips event handler attributes (on*)
     * - strips xlink:href/href attributes
     */
    private static function sanitizeSvg(string $svg): string
    {
        $svg = trim($svg);
        if ($svg === '' || !str_starts_with($svg, '<svg')) return '';

        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            // Prevent network access.
            $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            // Remove scripts
            while (true) {
                $scripts = $dom->getElementsByTagName('script');
                if ($scripts->length < 1) break;
                $scripts->item(0)?->parentNode?->removeChild($scripts->item(0));
            }

            // Strip dangerous attributes
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//@*') as $attr) {
                if (!$attr instanceof \DOMAttr) continue;
                $name = strtolower($attr->name);
                if (str_starts_with($name, 'on') || $name === 'href' || $name === 'xlink:href') {
                    $attr->ownerElement?->removeAttributeNode($attr);
                }
            }

            $out = $dom->saveXML($dom->documentElement);
            return is_string($out) ? $out : '';
        } catch (\Throwable $e) {
            return '';
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private static function applySvgPresentation(string $svg, int $sizePx, string $colour): string
    {
        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            $root = $dom->documentElement;
            if (!$root) return $svg;

            $root->setAttribute('width', (string) $sizePx);
            $root->setAttribute('height', (string) $sizePx);
            $root->setAttribute('aria-hidden', 'true');
            $root->setAttribute('focusable', 'false');

            // Add/merge style
            $style = (string) $root->getAttribute('style');
            $style = trim($style);
            $add = "display:block;color:{$colour}";
            $root->setAttribute('style', $style ? ($style . ';' . $add) : $add);

            $out = $dom->saveXML($root);
            return is_string($out) ? $out : $svg;
        } catch (\Throwable $e) {
            return $svg;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }
}
