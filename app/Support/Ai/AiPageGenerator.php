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

        $rules = [
            'Output ONLY HTML. No markdown. No backticks.',
            'Do NOT include <script> tags, inline JS, or event handler attributes (onclick, onload, etc.).',
            'No iframes, embeds, or external JS includes.',
            'All links must be http(s) or relative. No javascript: links.',
            'Keep the structure clean and readable.',
            'Use accessible markup (labels for inputs, sensible heading hierarchy).',
            'Do NOT include global site navigation bars/menus or global footer navigation; output page-body content sections only.',
            'You are redesigning a reference website. The design system provided describes the ACTUAL visual language of that site.',
            'Your output must look like that specific site was professionally redesigned — NOT like a generic Bootstrap or Tailwind starter template.',
            'Follow every visual character field in the design system: hero_treatment, section_rhythm, contrast_approach, spacing_density, visual_mood, border_radius, shadow_depth.',
            'Do NOT use default gradient colors (#667eea, #764ba2), generic grays (#f5f7fa, #f9fafb), or placeholder patterns unless the design system specifically calls for them.',
            'Use the ACTUAL brand colors from the design system for all backgrounds, gradients, accents, and interactive elements.',
            'Vary section structures across the page: combine full-width, split-layout, card-grid, and asymmetric sections instead of repeating one pattern.',
            'Preserve brand cues from the provided design system across every section.',
            'Avoid placeholder copy and weak one-line section descriptions.',
            'Generate substantial content depth with domain-specific details, not generic agency filler.',
            'Target at least 6 meaningful sections for standard pages, with substantial copy in each section.',
            'Do not place brand/logo assets as large body images in normal content sections.',
            'NEVER use the company logo image as an <img> source in body content sections — logos are handled by the navigation/footer system separately.',
            'Avoid repeating the same hero/content image throughout the page unless section is explicitly a gallery.',
            'IMAGE URL RULE: If the brief provides page-specific media URLs, you MUST use those EXACT URLs in your <img> src attributes and background-image CSS. Do NOT invent, guess, or hallucinate image URLs — only use URLs explicitly provided in the brief.',
        ];

        if ($businessContext !== '') {
            $rules[] = 'Business domain lock: ' . $businessContext;
            $rules[] = 'Every section and image choice must stay relevant to this business domain.';
        }

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

        // Design system styling rules
        if (!empty($designSystem)) {
            $rules[] = '';
            $rules[] = 'Design System Compliance:';
            if (isset($designSystem['primary_color'])) {
                $rules[] = '- Use primary color ' . $designSystem['primary_color'] . ' for CTAs, important elements, and visual emphasis.';
            }
            if (isset($designSystem['secondary_color'])) {
                $rules[] = '- Use secondary color ' . $designSystem['secondary_color'] . ' for secondary content, backgrounds.';
            }
            if (isset($designSystem['accent_color'])) {
                $rules[] = '- Use accent color ' . $designSystem['accent_color'] . ' sparingly for highlights.';
            }
            if (isset($designSystem['text_color'])) {
                $rules[] = '- Use text color ' . $designSystem['text_color'] . ' for body text.';
            }
            if (isset($designSystem['heading_font'])) {
                $rules[] = '- Use font-family: "' . $designSystem['heading_font'] . '" for headings.';
            }
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
        $parts[] = 'Brief:';
        $parts[] = $brief;

        $parts[] = '';
        $parts[] = 'CONTENT REQUIREMENTS:';
        $parts[] = '- Use clear, domain-specific section headings (not generic "Our Services" or "Why Choose Us")';
        $parts[] = '- Include a strong above-the-fold section that follows the hero_treatment defined above';
        $parts[] = '- Include at least 5 supporting sections appropriate to the page type';
        $parts[] = '- Expand section copy with specifics: processes, outcomes, differentiators, practical examples';
        $parts[] = '- Avoid generic corporate filler: "innovation", "quality", "trusted", "cutting-edge" without context';
        $parts[] = '- Keep section narratives distinct; avoid copy-paste repetition across sections';
        $parts[] = '- Include a CTA section with the button treatment defined in the design system above';

        $parts[] = '';
        $parts[] = 'VISUAL EXECUTION RULES:';
        $parts[] = '- Follow the section_rhythm from the design system: replicate the flow pattern described above';
        $parts[] = '- Apply the contrast_approach: use the described background alternation/contrast strategy';
        $parts[] = '- Match the spacing_density: if "airy" use generous padding (80-120px sections), if "dense" use compact spacing (40-60px)';
        $parts[] = '- Use the border_radius consistently on ALL cards, buttons, and image containers';
        $parts[] = '- Apply the shadow_depth described: if "dramatic" use strong shadows, if "none" avoid box-shadow';
        $parts[] = '- Typography scale must follow the described hierarchy — don\'t default to 48/32/20 unless that matches';
        $parts[] = '- Images: max-width 100%, height auto, radius matching the design system';
        $parts[] = '- Ensure image references are context-relevant for the business domain and section purpose';

        $parts[] = '';
        $parts[] = 'ANTI-TEMPLATE RULES (mandatory):';
        $parts[] = '- Do NOT use generic gradient heroes (#667eea → #764ba2 or similar defaults)';
        $parts[] = '- Do NOT use the same card grid pattern for every section';
        $parts[] = '- Do NOT center-align all text by default — use left-aligned body text';
        $parts[] = '- Do NOT use emoji icons (🎯 📱 etc.) unless the mood is casual/playful';
        $parts[] = '- Do NOT use generic light-gray (#f5f7fa, #f9fafb) backgrounds unless the design system specifies them';
        $parts[] = '- Do NOT repeat the same section structural pattern more than twice on one page';
        $parts[] = '- Use the ACTUAL design system colors for backgrounds, gradients, and accents — not safe defaults';
        $parts[] = '- Vary section structures: combine full-width, split layouts, overlapping elements, asymmetric grids';

        $parts[] = '';
        $parts[] = 'SECTION LAYOUT VARIETY (critical — prevents clone pages from looking identical):';
        $parts[] = '- Do NOT use a repeating "text-left / image-right" alternating layout for every section';
        $parts[] = '- Each page should use at least 4 DIFFERENT section layouts from this list:';
        $parts[] = '  1. Full-width hero with background image/color and overlay text';
        $parts[] = '  2. Two-column split (image left, text right)';
        $parts[] = '  3. Two-column split (text left, image right)';
        $parts[] = '  4. Three or four column card/feature grid';
        $parts[] = '  5. Full-width content band with centered text (no image)';
        $parts[] = '  6. Asymmetric grid (60/40 or 70/30 split)';
        $parts[] = '  7. Icon + text feature list (vertical or horizontal)';
        $parts[] = '  8. Testimonial/quote block with distinctive styling';
        $parts[] = '  9. Stats/numbers row with large typography';
        $parts[] = '  10. Full-width image with overlaid text';
        $parts[] = '- NEVER use the same two-column split direction more than twice consecutively';
        $parts[] = '- NEVER place a brand logo as the image in a content section — use contextual images only';
        $parts[] = '- Images (img tags) should depict scenes, products, or environments relevant to the section topic — NOT the company logo';

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
        $final .= "\n\n---\nOnly output HTML. Follow the visual design system exactly. Do not fall back to generic template patterns.";

        return $final;
    }
}
