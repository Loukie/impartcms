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
            'Avoid generic starter-template patterns and repetitive section clones.',
            'Use an intentional visual concept with clear hierarchy, spacing rhythm, and contrast.',
            'Preserve brand cues from the provided design system across every section.',
            'Avoid placeholder copy and weak one-line section descriptions.',
            'Generate substantial content depth with domain-specific details, not generic agency filler.',
            'Target at least 6 meaningful sections for standard pages, with substantial copy in each section.',
            'Do not place brand/logo assets as large body images in normal content sections.',
            'Avoid repeating the same hero/content image throughout the page unless section is explicitly a gallery.',
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

        if (!empty($designSystem)) {
            $parts[] = '';
            $parts[] = 'Design System (apply consistently):';
            if (isset($designSystem['layout_pattern'])) {
                $parts[] = '- Layout: ' . $designSystem['layout_pattern'];
            }
            if (isset($designSystem['nav_style'])) {
                $parts[] = '- Navigation: ' . $designSystem['nav_style'];
            }
            if (isset($designSystem['cta_style'])) {
                $parts[] = '- CTA Style: ' . $designSystem['cta_style'];
            }
        }

        $parts[] = '';
        $parts[] = 'Brief:';
        $parts[] = $brief;

        $parts[] = '';
        $parts[] = 'Content guidelines:';
        $parts[] = '- Use clear section headings.';
        $parts[] = '- Include a strong above-the-fold section.';
        $parts[] = '- Include at least 5 supporting sections (features, benefits, FAQ, testimonials, services, process, proof) when appropriate.';
        $parts[] = '- Include a CTA section.';
        $parts[] = '- Expand section copy with specifics: processes, outcomes, differentiators, and practical examples.';
        $parts[] = '- Avoid generic placeholders such as "innovation", "quality", "trusted" without concrete context.';
        $parts[] = '- Keep section narratives distinct; avoid copy-paste repetition across sections.';
        $parts[] = '';
        $parts[] = 'LAYOUT PATTERNS - Use generously for visual richness:';
        $parts[] = '- Hero sections: use <section> with centered text, padding 60-80px, large heading with supporting text.';
        $parts[] = '- Service/Feature cards: use 2-3 column responsive grid, card containers with padding, icons/emoji, clear copy.';
        $parts[] = '- Testimonials: use <blockquote> with author name/title, quotation marks styling, proper spacing.';
        $parts[] = '- Image + text sections: alternate image-left/text-right, image-right/text-left for visual flow and hierarchy.';
        $parts[] = '- Stats/numbers: grid layout with large numbers (font-size 36-48px), smaller labels beneath (font-size 14-16px).';
        $parts[] = '- CTA sections: full-width, centered layout with prominent button, supporting text, contrast with surroundings.';
        $parts[] = '- Lists: use ul/ol for scannable content, add icons or emojis before items for visual interest.';
        $parts[] = '';
        $parts[] = 'STYLING FOR PROFESSIONAL APPEARANCE:';
        $parts[] = '- Set section padding: min 40px top/bottom, 20px left/right (desktop should be 60-80px).';
        $parts[] = '- Use alternating background colors: white, light gray (#f5f7fa), or brand color for section separation.';
        $parts[] = '- Cards: add box-shadow: 0 2px 8px rgba(0,0,0,0.1), border-radius: 8px, padding 24-32px.';
        $parts[] = '- Typography: use multiple heading sizes for hierarchy (h1 for page, h2 for sections 32-40px, h3 for subsections 24-28px), body text 16px.';
        $parts[] = '- Line height: 1.2 for headings, 1.6 for body text for readability.';
        $parts[] = '- Buttons: use primary color, padding 12px 24px, border-radius: 4px, white text, hover effect (opacity or shadow).';
        $parts[] = '- Images: max-width 100%, height auto, border-radius: 8px, add subtle shadow if overlaid on backgrounds.';
        $parts[] = '- Ensure image references are context-relevant for the business domain and section purpose.';

        if (!$fullDocument) {
            $parts[] = '';
            $parts[] = 'IMPORTANT: This HTML will be injected into an existing Blade theme. Do not include the outer document tags.';
        }

        if ($styleMode !== 'inline') {
            $parts[] = '';
            $parts[] = 'Styling: you may use classes, but avoid depending on any specific framework unless the brief explicitly says so.';
        }

        $parts[] = '';
        $parts[] = 'EXAMPLE SECTION STRUCTURES (follow these patterns):';
        $parts[] = '';
        $parts[] = 'Hero Example: <section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 20px; text-align: center;"><div style="max-width: 800px; margin: 0 auto;"><h1 style="font-size: 48px; margin-bottom: 16px; margin-top: 0;">Main Headline</h1><p style="font-size: 18px; line-height: 1.6; margin-bottom: 32px; opacity: 0.95;">Supporting description goes here</p><a href="#" style="background: white; color: #667eea; padding: 14px 32px; border-radius: 4px; text-decoration: none; font-weight: 600; display: inline-block; transition: transform 0.2s;">Get Started</a></div></section>';
        $parts[] = '';
        $parts[] = 'Cards Grid Example: <section style="padding: 60px 20px; background: #f9fafb;"><div style="max-width: 1200px; margin: 0 auto;"><h2 style="font-size: 32px; text-align: center; margin-bottom: 48px;">Our Services</h2><div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;"><div style="background: white; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s;"><div style="font-size: 32px; margin-bottom: 16px;">🎯</div><h3 style="margin-top: 0; font-size: 20px;">Card Title</h3><p style="color: #666; line-height: 1.6;">Clear description of the service or feature.</p></div></div></div></section>';
        $parts[] = '';
        $parts[] = 'Image + Text Example: <section style="padding: 60px 20px;"><div style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;"><img src="image.jpg" style="width: 100%; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);" /><div><h2 style="font-size: 32px; margin-top: 0;">Section Heading</h2><p style="color: #666; line-height: 1.6; font-size: 16px;">Content paragraph with details.</p><ul style="line-height: 1.8; font-size: 16px;"><li>Feature one</li><li>Feature two</li><li>Feature three</li></ul></div></div></section>';
        $parts[] = '';
        $parts[] = 'Testimonial Example: <section style="padding: 60px 20px; background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);"><div style="max-width: 800px; margin: 0 auto;"><blockquote style="font-size: 18px; font-style: italic; margin: 0 0 24px 0; padding: 24px 24px 24px 32px; border-left: 4px solid #667eea; background: white; border-radius: 4px; line-height: 1.6;">&quot;This is a powerful testimonial quote that validates the service or product.&quot;</blockquote><p style="text-align: center; margin: 0; color: #333;"><strong>Author Name</strong><br /><span style="color: #666; font-size: 14px;">Title or Company</span></p></div></section>';

        // Prevent prompt injection from brief by explicitly delimiting it.
        $final = implode("\n", $parts);
        $final .= "\n\n---\nOnly output HTML.";

        return $final;
    }
}
