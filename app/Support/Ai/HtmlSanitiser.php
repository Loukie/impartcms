<?php

namespace App\Support\Ai;

/**
 * Very defensive HTML sanitiser.
 *
 * Goal:
 * - Allow “CMS page HTML” (divs, headings, tables, forms, images, etc.)
 * - Remove scripts / inline event handlers / javascript: URLs
 * - Keep it dependency-free (no Composer package required)
 *
 * Note:
 * - This is not a perfect purifier, but it meaningfully reduces XSS risk.
 * - If you want maximum coverage, swap this for HTMLPurifier later.
 */
class HtmlSanitiser
{
    /**
     * @var array<string, true>
     */
    private array $allowedTags = [
        // Document
        'html' => true,
        'head' => true,
        'body' => true,
        'meta' => true,
        'title' => true,
        'style' => true,
        'link' => true,
        // Layout
        'div' => true,
        'span' => true,
        'section' => true,
        'main' => true,
        'header' => true,
        'footer' => true,
        'nav' => true,
        'article' => true,
        'aside' => true,
        // Text
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'p' => true,
        'br' => true,
        'hr' => true,
        'strong' => true,
        'b' => true,
        'em' => true,
        'i' => true,
        'small' => true,
        'code' => true,
        'pre' => true,
        'blockquote' => true,
        // Lists
        'ul' => true,
        'ol' => true,
        'li' => true,
        // Media
        'img' => true,
        'figure' => true,
        'figcaption' => true,
        'video' => true,
        'source' => true,
        // Links
        'a' => true,
        // Tables
        'table' => true,
        'thead' => true,
        'tbody' => true,
        'tfoot' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'caption' => true,
        // Forms
        'form' => true,
        'label' => true,
        'input' => true,
        'textarea' => true,
        'button' => true,
        'select' => true,
        'option' => true,
    ];

    /**
     * @var array<string, true>
     */
    private array $globalAllowedAttributes = [
        'class' => true,
        'id' => true,
        'style' => true,
        'title' => true,
        'aria-label' => true,
        'aria-hidden' => true,
        'role' => true,
        'data-*' => true,
    ];

    /**
     * @var array<string, array<string,true>>
     */
    private array $tagAllowedAttributes = [
        'a' => [
            'href' => true,
            'target' => true,
            'rel' => true,
        ],
        'img' => [
            'src' => true,
            'alt' => true,
            'width' => true,
            'height' => true,
            'loading' => true,
            'decoding' => true,
        ],
        'form' => [
            'action' => true,
            'method' => true,
        ],
        'input' => [
            'type' => true,
            'name' => true,
            'value' => true,
            'placeholder' => true,
            'checked' => true,
            'selected' => true,
            'required' => true,
            'disabled' => true,
            'readonly' => true,
            'min' => true,
            'max' => true,
            'step' => true,
        ],
        'textarea' => [
            'name' => true,
            'placeholder' => true,
            'rows' => true,
            'cols' => true,
            'required' => true,
            'disabled' => true,
            'readonly' => true,
        ],
        'button' => [
            'type' => true,
            'name' => true,
            'value' => true,
            'disabled' => true,
        ],
        'select' => [
            'name' => true,
            'required' => true,
            'disabled' => true,
        ],
        'option' => [
            'value' => true,
            'selected' => true,
        ],
        'meta' => [
            'charset' => true,
            'name' => true,
            'content' => true,
            'property' => true,
        ],
        'link' => [
            'rel' => true,
            'href' => true,
            'type' => true,
        ],
        'source' => [
            'src' => true,
            'type' => true,
        ],
        'video' => [
            'controls' => true,
            'autoplay' => true,
            'muted' => true,
            'loop' => true,
            'playsinline' => true,
            'poster' => true,
        ],
    ];

    public function clean(string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Decode literal escape sequences that LLMs sometimes emit as two characters
        // (e.g. backslash-n instead of a real newline) so they don't render as "\n" on the page.
        $html = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $html);

        // Strip markdown code fences that LLMs sometimes wrap around HTML output.
        $html = $this->stripMarkdownFences($html);

        $isFullDoc = (bool) (preg_match('/<!doctype\s+html/i', $html) || preg_match('/<html\b/i', $html));

        // Fast pre-scrub: remove script blocks entirely.
        $html = preg_replace('#<\s*script\b[^>]*>(.*?)<\s*/\s*script>#is', '', $html) ?? $html;
        $html = preg_replace('#<\s*iframe\b[^>]*>(.*?)<\s*/\s*iframe>#is', '', $html) ?? $html;
        $html = preg_replace('#<\s*object\b[^>]*>(.*?)<\s*/\s*object>#is', '', $html) ?? $html;
        $html = preg_replace('#<\s*embed\b[^>]*>(.*?)<\s*/\s*embed>#is', '', $html) ?? $html;

        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        // Force UTF-8.
        // - For fragments we wrap into a minimal document, then later extract <body> contents.
        // - For full documents we load as-is and return the full HTML.
        if ($isFullDoc) {
            $dom->loadHTML($html);
        } else {
            $wrapped = '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
            $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*');
        if ($nodes) {
            // Iterate backwards to safely remove nodes.
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $node = $nodes->item($i);
                if (!$node instanceof \DOMElement) {
                    continue;
                }

                $tag = strtolower($node->tagName);
                if (!isset($this->allowedTags[$tag])) {
                    $this->unwrapNode($node);
                    continue;
                }

                $this->scrubAttributes($node);
            }
        }

        if ($isFullDoc) {
            return trim((string) $dom->saveHTML());
        }

        // Extract our injected body contents.
        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private function unwrapNode(\DOMElement $node): void
    {
        $parent = $node->parentNode;
        if (!$parent) {
            $node->remove();
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }

    private function scrubAttributes(\DOMElement $el): void
    {
        if (!$el->hasAttributes()) {
            return;
        }

        $tag = strtolower($el->tagName);
        $tagAttrs = $this->tagAllowedAttributes[$tag] ?? [];

        // Copy attribute list first to avoid mutation issues.
        $attrs = [];
        foreach ($el->attributes as $attr) {
            $attrs[] = $attr->name;
        }

        foreach ($attrs as $name) {
            $lname = strtolower($name);
            $value = (string) $el->getAttribute($name);

            // Remove any inline event handlers (onclick, onload, ...)
            if (str_starts_with($lname, 'on')) {
                $el->removeAttribute($name);
                continue;
            }

            // Allow data-* globally
            if (str_starts_with($lname, 'data-')) {
                continue;
            }

            // Allow aria-* globally
            if (str_starts_with($lname, 'aria-')) {
                continue;
            }

            $isAllowed = isset($this->globalAllowedAttributes[$lname]) || isset($tagAttrs[$lname]);
            if (!$isAllowed) {
                $el->removeAttribute($name);
                continue;
            }

            // Special handling for href/src
            if ($lname === 'href' || $lname === 'src' || $lname === 'action') {
                if (!$this->isSafeUrl($value, $tag, $lname)) {
                    $el->removeAttribute($name);
                    continue;
                }
            }

            // Scrub style attribute
            if ($lname === 'style') {
                $safe = $this->scrubStyle($value);
                if ($safe === '') {
                    $el->removeAttribute($name);
                } else {
                    $el->setAttribute($name, $safe);
                }
            }

            // Ensure target _blank has rel noopener
            if ($tag === 'a' && $lname === 'target' && strtolower(trim($value)) === '_blank') {
                $rel = (string) $el->getAttribute('rel');
                $needles = ['noopener', 'noreferrer'];
                $parts = preg_split('/\s+/', trim($rel)) ?: [];
                foreach ($needles as $n) {
                    if (!in_array($n, $parts, true)) {
                        $parts[] = $n;
                    }
                }
                $el->setAttribute('rel', trim(implode(' ', array_filter($parts))));
            }
        }
    }

    private function isSafeUrl(string $url, string $tag, string $attr): bool
    {
        $u = trim($url);
        if ($u === '') {
            return true;
        }

        $low = strtolower($u);

        // Block javascript: and vbscript:
        if (str_starts_with($low, 'javascript:') || str_starts_with($low, 'vbscript:')) {
            return false;
        }

        // Allow mailto/tel for links
        if ($attr === 'href' && (str_starts_with($low, 'mailto:') || str_starts_with($low, 'tel:'))) {
            return true;
        }

        // Allow same-page anchors
        if ($attr === 'href' && str_starts_with($low, '#')) {
            return true;
        }

        // Allow relative URLs
        if (!preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $u)) {
            return true;
        }

        // If it has a scheme, only allow http/https.
        return str_starts_with($low, 'https://') || str_starts_with($low, 'http://');
    }

    private function scrubStyle(string $style): string
    {
        $s = trim($style);
        if ($s === '') {
            return '';
        }

        $low = strtolower($s);
        // Kill obvious CSS injection vectors
        if (str_contains($low, 'expression(')) {
            return '';
        }
        if (str_contains($low, 'javascript:')) {
            return '';
        }
        if (str_contains($low, '@import')) {
            return '';
        }
        // Very conservative: remove url(...) usage
        if (preg_match('/url\s*\(/i', $s)) {
            return '';
        }

        // Basic length clamp to avoid someone pasting megabytes.
        if (strlen($s) > 5000) {
            $s = substr($s, 0, 5000);
        }

        return $s;
    }

    /**
     * Strip markdown code fences that LLMs sometimes wrap around HTML output.
     * Handles ```html, ```css, bare ```, and similar variants.
     */
    private function stripMarkdownFences(string $text): string
    {
        // Remove opening fence: ```html, ```css, ```htm, or bare ```
        $text = preg_replace('/^```(?:html|css|htm)?\s*$/mi', '', $text) ?? $text;

        return trim($text);
    }
}
