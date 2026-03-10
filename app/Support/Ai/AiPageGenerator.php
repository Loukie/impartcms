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
     *
     * Options:
     * - title: page title
     * - style_mode: inline|classes
     * - full_document: bool
     * - design_system: array of design system (optional)
      * - business_context: inferred business/domain context string (optional)
     */
    public function generateHtml(string $brief, array $options = []): array
    {
        $title = trim((string) ($options['title'] ?? ''));
        $styleMode = (string) ($options['style_mode'] ?? 'inline'); // inline|classes
        $fullDocument = (bool) ($options['full_document'] ?? false);
        $designSystem = (array) ($options['design_system'] ?? []);
          $businessContext = trim((string) ($options['business_context'] ?? ''));

          $instructions = $this->buildInstructions($styleMode, $fullDocument, $designSystem, $businessContext);
          $input = $this->buildInput($brief, $title, $styleMode, $fullDocument, $designSystem, $businessContext);

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

    private function buildInstructions(string $styleMode, bool $fullDocument, array $designSystem = [], string $businessContext = ''): string
    {
        $styleMode = $styleMode === 'classes' ? 'classes' : 'inline';

        $primaryColor = $designSystem['primary_color'] ?? '#D4A03C';
        $secondaryColor = $designSystem['secondary_color'] ?? '#111827';
        $accentColor = $designSystem['accent_color'] ?? '#8F806A';
        $textColor = $designSystem['text_color'] ?? '#333333';
        $bgColor = $designSystem['background_color'] ?? '#ffffff';
        $headingFont = $designSystem['heading_font'] ?? 'Georgia, serif';
        $bodyFont = $designSystem['body_font'] ?? 'system-ui, sans-serif';

        $rules = [
            'You are a senior UI/UX Design Director producing premium, production-ready HTML.',
            'Output ONLY HTML. No markdown. No backticks. No commentary.',
            'Do NOT include <script> tags, inline JS, or event handler attributes (onclick, onload, etc.).',
            'No iframes, embeds, or external JS includes.',
            'All links must be http(s) or relative. No javascript: links.',
            'Do NOT include global site navigation bars/menus or global footer navigation; output page-body content sections only.',
            '',
            '=== DESIGN DIRECTOR MANDATE ===',
            'Your output must look like a $50,000 custom website redesign — NOT a starter template.',
            'Every section must have DISTINCT visual treatment. If two sections look structurally identical, you have FAILED.',
            'Output must pass this test: "Would a design director approve this for a luxury brand?" If no, revise.',
            '',
            '=== CSS CUSTOM PROPERTIES (MANDATORY) ===',
            'Include a <style> tag at the top of your output with these CSS custom properties:',
            ':root {',
            '  --color-primary: ' . $primaryColor . ';',
            '  --color-secondary: ' . $secondaryColor . ';',
            '  --color-accent: ' . $accentColor . ';',
            '  --color-text: ' . $textColor . ';',
            '  --color-bg: ' . $bgColor . ';',
            '  --font-heading: "' . $headingFont . '", Georgia, serif;',
            '  --font-body: "' . $bodyFont . '", system-ui, sans-serif;',
            '}',
            'Then USE these custom properties throughout all sections. This ensures brand consistency.',
            '',
            '=== TYPOGRAPHY SYSTEM ===',
            'Define a clear type hierarchy using var(--font-heading) and var(--font-body):',
            '- H1: clamp(2.5rem, 5vw, 4rem), font-weight: 400-500, line-height: 1.1, letter-spacing: -0.02em',
            '- H2: clamp(1.8rem, 3.5vw, 2.8rem), font-weight: 400-500, line-height: 1.2',
            '- H3: clamp(1.2rem, 2vw, 1.6rem), font-weight: 500-600, line-height: 1.3',
            '- Body: 0.95-1.05rem, font-weight: 400, line-height: 1.65',
            '- Section labels/overlines: 0.7-0.8rem, font-weight: 600, letter-spacing: 0.15-0.25em, uppercase, color: var(--color-primary)',
            'Use the heading font for ALL h1/h2/h3 elements. Use body font for paragraphs.',
            '',
            '=== SECTION ARCHITECTURE (CRITICAL) ===',
            'Each page must have 7-10 sections with DISTINCT layout treatment:',
            '',
            'SECTION TYPE 1 — Full-viewport hero (min-height: 90vh)',
            '  Background image with dark overlay, absolute positioning, text at bottom-left or center.',
            '  Include: overline label, large H1, supporting paragraph (max-width: 540px), 1-2 CTA buttons.',
            '  Text color: white. Overlay: linear-gradient with rgba(0,0,0,0.6) to rgba(0,0,0,0.3).',
            '',
            'SECTION TYPE 2 — Feature ribbon / icon bar',
            '  Dark background (var(--color-secondary)). 3-4 columns with inline SVG icons.',
            '  Short title + 1-line description per feature. Subtle dividers between items.',
            '',
            'SECTION TYPE 3 — Alternating service cards (image + text)',
            '  Two-column grid, alternating image side (odd: img left, even: img right).',
            '  Image container: overflow hidden, object-fit cover, hover scale(1.05) transition.',
            '  Content side: numbered section (large faded number like "01"), title, paragraph, arrow link.',
            '  Wrap in a card with border-radius and subtle box-shadow.',
            '',
            'SECTION TYPE 4 — Full-width split (50/50)',
            '  Left: full-bleed image. Right: dark background with text content.',
            '  Or reverse. No container constraints on the image side.',
            '',
            'SECTION TYPE 5 — 3-column value proposition cards',
            '  Centered header (label + title + subtitle). Below: 3 cards in a grid.',
            '  Each card: icon circle (primary color bg at 8% opacity), title, description.',
            '  Cards have border, hover: border-color changes to primary, translateY(-6px), shadow.',
            '',
            'SECTION TYPE 6 — Testimonial slider / grid',
            '  Dark background. 3-column card grid.',
            '  Each card: star rating (SVG stars filled with primary), italic quote text, author avatar + name.',
            '  Cards: dark-card background (#111), subtle border, hover border glow.',
            '',
            'SECTION TYPE 7 — Blog/insights preview grid',
            '  3-column card grid with image, tag badge, title, excerpt.',
            '  Image: overflow hidden, hover scale. Tag: absolute positioned pill badge.',
            '',
            'SECTION TYPE 8 — Partner/logo bar',
            '  Light background. Centered label. Flex row of partner logos.',
            '  Logos: grayscale filter, hover: full color. Height: 36px, object-fit: contain.',
            '',
            'SECTION TYPE 9 — Stats row',
            '  3-4 columns of large numbers (font-heading, 3rem+) with label beneath.',
            '  Can use primary color for the numbers.',
            '',
            'SECTION TYPE 10 — CTA banner',
            '  Dark background with subtle radial gradient glow (primary color at 5-8% opacity).',
            '  Centered: label, heading, subtitle, primary CTA button.',
            '',
            'Use at least 5 DIFFERENT section types per page. NEVER repeat the exact same layout.',
            '',
            '=== VISUAL POLISH REQUIREMENTS ===',
            '- Every section label/overline: use a horizontal line (40px wide, 1px, primary color) before text.',
            '- Background alternation: white → cream (#FAF8F4) → dark (#0A0A0A) with purpose, not generic #f9fafb.',
            '- All buttons: uppercase, letter-spacing: 0.12em, font-weight: 600, font-size: 0.78rem, padding: 1rem 2.2rem.',
            '- Primary buttons: background var(--color-primary), color dark. Hover: lighten + translateY(-2px) + box-shadow.',
            '- Outline buttons: transparent bg, 1px border rgba(255,255,255,0.3), white text. Hover: border becomes primary.',
            '- Arrow links: primary color text, inline-flex with right-arrow SVG, hover: arrow translateX(4px).',
            '- Images: always use object-fit: cover on containers, overflow: hidden, border-radius matching design system.',
            '- Cards: transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) for hover effects.',
            '- Use clamp() for responsive font sizes instead of fixed px values.',
            '- max-width: 1280px containers with padding: 0 clamp(1.25rem, 4vw, 3rem).',
            '',
            '=== RESPONSIVE DESIGN (MANDATORY) ===',
            'Include responsive media queries in your <style> tag:',
            '- @media (max-width: 1024px): single column for 2-col grids, reduce padding',
            '- @media (max-width: 768px): single column for 3-col grids, reduce font sizes',
            '- @media (max-width: 480px): stack everything, min padding',
            'All grid layouts must collapse gracefully. Use grid-template-columns: 1fr !important at mobile.',
            '',
            '=== BANNED PATTERNS (instant fail) ===',
            '- Do NOT use #667eea, #764ba2, or any default gradient from templates.',
            '- Do NOT use #f5f7fa, #f9fafb, #f3f4f6 as section backgrounds — use design system colors instead.',
            '- Do NOT use emoji icons (🎯 📱 etc.)',
            '- Do NOT center-align all body text — use left-aligned for paragraphs.',
            '- Do NOT repeat the same two-column split direction more than twice.',
            '- Do NOT use the same structural pattern for consecutive sections.',
            '- Do NOT use generic filler: "innovation", "quality", "trusted", "cutting-edge", "modern solutions".',
            '- Do NOT use company logo images in body content sections.',
            '- Do NOT invent image URLs — only use URLs explicitly provided in the brief.',
            '- Do NOT output plain sections with just a heading and one paragraph — every section needs visual depth.',
            '',
            '=== ACCESSIBILITY ===',
            '- Sensible heading hierarchy (one H1, then H2s, then H3s).',
            '- Alt text on all images.',
            '- Sufficient color contrast for text readability.',
            '- Focus-visible styles on interactive elements.',
        ];

        if ($businessContext !== '') {
            $rules[] = '';
            $rules[] = 'Business domain lock: ' . $businessContext;
            $rules[] = 'Every section, heading, and image choice must stay relevant to this business domain.';
        }

        if ($fullDocument) {
            $rules[] = 'Return a FULL HTML document including <!doctype html>, <html>, <head>, and <body>. Include the <style> tag in <head>.';
        } else {
            $rules[] = 'Return an HTML FRAGMENT (no <html>, <head>, <body>, or <!doctype html>). Start with a <style> tag containing the CSS custom properties and responsive rules, followed by the section HTML.';
        }

        if ($styleMode === 'inline') {
            $rules[] = 'Use a combination of CSS classes (defined in your <style> tag) and inline styles. Do not rely on Tailwind or Bootstrap.';
        } else {
            $rules[] = 'Use class attributes defined in your <style> tag. Keep classes semantic and minimal.';
        }

        return implode("\n", $rules);
    }

    private function buildInput(string $brief, string $title, string $styleMode, bool $fullDocument, array $designSystem = [], string $businessContext = ''): string
    {
        $brief = trim($brief);
        $title = trim($title);

        // Add icon shortcode examples to brief context
        $primary = $designSystem['primary_color'] ?? '#3498db';
        $iconDocs = "\n\n🎨 ICON SHORTCODES (use these instead of icon images):\n";
        $iconDocs .= "FontAwesome: [icon kind=\"fa\" value=\"fa-solid fa-house\" size=\"24\" colour=\"$primary\"]\n";
        $iconDocs .= "Lucide: [icon kind=\"lucide\" value=\"home\" size=\"24\" colour=\"$primary\"]\n";
        $iconDocs .= "Common icons: fa-check, fa-users, fa-shield, fa-star, fa-heart, fa-phone, fa-envelope, fa-briefcase\n";
        $iconDocs .= "Use icons for: service cards, feature lists, contact info, benefits sections\n";
        
        $brief = $brief . $iconDocs;

        $parts = [];

        if ($title !== '') {
            $parts[] = 'Page title: ' . $title;
        }

        if ($businessContext !== '') {
            $parts[] = 'Business context: ' . $businessContext;
            $parts[] = 'Critical: keep all content and media references aligned with this domain.';
        }

        // Inject the full visual design system — not just colors, but the visual character
        if (!empty($designSystem)) {
            $parts[] = '';
            $parts[] = '🎨 VISUAL DESIGN SYSTEM (REFERENCE-LOCKED — follow this exactly):';
            $parts[] = '';

            // Colors
            $parts[] = 'Color palette:';
            if (isset($designSystem['primary_color'])) {
                $parts[] = '  Primary: ' . $designSystem['primary_color'] . ' (CTAs, key accents, interactive elements)';
            }
            if (isset($designSystem['secondary_color'])) {
                $parts[] = '  Secondary: ' . $designSystem['secondary_color'] . ' (supporting elements, secondary backgrounds)';
            }
            if (isset($designSystem['accent_color'])) {
                $parts[] = '  Accent: ' . $designSystem['accent_color'] . ' (highlights, badges, small emphasis)';
            }
            if (isset($designSystem['text_color'])) {
                $parts[] = '  Text: ' . $designSystem['text_color'];
            }
            if (isset($designSystem['background_color'])) {
                $parts[] = '  Background: ' . $designSystem['background_color'];
            }

            // Typography
            $parts[] = '';
            $parts[] = 'Typography:';
            if (isset($designSystem['heading_font'])) {
                $parts[] = '  Heading font: "' . $designSystem['heading_font'] . '"';
            }
            if (isset($designSystem['body_font'])) {
                $parts[] = '  Body font: "' . $designSystem['body_font'] . '"';
            }
            if (isset($designSystem['typography_scale']) && trim((string) $designSystem['typography_scale']) !== '') {
                $parts[] = '  Scale: ' . $designSystem['typography_scale'];
            }

            // Visual character — the key differentiation from generic templates
            $parts[] = '';
            $parts[] = 'Visual character (MUST follow):';
            if (isset($designSystem['hero_treatment']) && trim((string) $designSystem['hero_treatment']) !== '') {
                $parts[] = '  Hero treatment: ' . $designSystem['hero_treatment'];
            }
            if (isset($designSystem['section_rhythm']) && trim((string) $designSystem['section_rhythm']) !== '') {
                $parts[] = '  Section rhythm: ' . $designSystem['section_rhythm'];
            }
            if (isset($designSystem['section_backgrounds']) && trim((string) $designSystem['section_backgrounds']) !== '') {
                $parts[] = '  Section backgrounds: ' . $designSystem['section_backgrounds'];
            }
            if (isset($designSystem['contrast_approach']) && trim((string) $designSystem['contrast_approach']) !== '') {
                $parts[] = '  Contrast approach: ' . $designSystem['contrast_approach'];
            }
            if (isset($designSystem['spacing_density']) && trim((string) $designSystem['spacing_density']) !== '') {
                $parts[] = '  Spacing density: ' . $designSystem['spacing_density'];
            }
            if (isset($designSystem['visual_mood']) && trim((string) $designSystem['visual_mood']) !== '') {
                $parts[] = '  Visual mood: ' . $designSystem['visual_mood'];
            }

            // Component treatment
            $parts[] = '';
            $parts[] = 'Component treatment:';
            if (isset($designSystem['cta_style']) && trim((string) $designSystem['cta_style']) !== '') {
                $parts[] = '  CTA buttons: ' . $designSystem['cta_style'];
            }
            if (isset($designSystem['border_radius']) && trim((string) $designSystem['border_radius']) !== '') {
                $parts[] = '  Border radius: ' . $designSystem['border_radius'];
            }
            if (isset($designSystem['shadow_depth']) && trim((string) $designSystem['shadow_depth']) !== '') {
                $parts[] = '  Shadow depth: ' . $designSystem['shadow_depth'];
            }
            if (isset($designSystem['layout_pattern']) && trim((string) $designSystem['layout_pattern']) !== '') {
                $parts[] = '  Layout pattern: ' . $designSystem['layout_pattern'];
            }
        }

        $parts[] = '';
        $parts[] = 'PAGE BRIEF:';
        $parts[] = $brief;

        $parts[] = '';
        $parts[] = 'CONTENT FOCUS:';
        $parts[] = '- Use domain-specific section headings that reflect this exact business.';
        $parts[] = '- Include a strong above-the-fold hero section following the hero_treatment above.';
        $parts[] = '- Include 6-8 unique supporting sections appropriate to the page type.';
        $parts[] = '- Expand section copy with specifics: processes, outcomes, differentiators.';
        $parts[] = '- Include a final CTA section following the design system button treatment.';
        $parts[] = '- Every image src must come from the URLs provided in the brief — do NOT invent image URLs.';

        if (!$fullDocument) {
            $parts[] = '';
            $parts[] = 'This HTML will be injected into an existing theme. Do not include outer document tags.';
        }

        // Prevent prompt injection from brief by explicitly delimiting it.
        $final = implode("\n", $parts);
        $final .= "\n\n---\nOnly output HTML with embedded <style> tag. Follow the CSS custom properties and section architecture from your instructions exactly. Do not fall back to generic template patterns.";

        return $final;
    }
}
