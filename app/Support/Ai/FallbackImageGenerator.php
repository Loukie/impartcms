<?php

namespace App\Support\Ai;

use App\Models\MediaFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FallbackImageGenerator
{
    public function __construct(
        private readonly AiImageClientInterface $imageClient,
        private readonly ?int $userId = null,
        private readonly string $disk = 'public',
    ) {}

    /**
     * Generate a real fallback image file and register it in Media Library.
     *
    * Strategy:
    * 1) Try AI image generation (preferred)
    * 2) If AI generation fails, fetch a free temporary image
    * 3) If that fails, fall back to generated SVG
     */
    public function create(array $context = []): MediaFile
    {
        $folder = now()->format('Y/m');
        $asset = $this->buildImageAsset($context);
        $filename = 'fallback-' . Str::uuid() . '.' . $asset['extension'];
        $path = 'media/' . $folder . '/' . $filename;

        Storage::disk($this->disk)->put($path, $asset['binary']);

        $bytes = strlen($asset['binary']);
        $brand = $this->brandFromContext($context);
        $title = $brand !== '' ? ($brand . ' Fallback Image') : 'AI Clone Fallback Image';
        [$width, $height] = $this->detectDimensions($path, $asset['mime_type']);

        $source = (string) ($asset['source'] ?? 'static');
        $sourceLabel = match ($source) {
            'ai' => 'AI generated automatically during site clone when source images were unavailable.',
            'free' => 'Temporary free-stock fallback image downloaded automatically during site clone.',
            default => 'Generated automatically during AI site clone to prevent broken images.',
        };

        return MediaFile::query()->create([
            'disk' => $this->disk,
            'path' => $path,
            'folder' => $folder,
            'original_name' => $filename,
            'filename' => $filename,
            'mime_type' => $asset['mime_type'],
            'size' => $bytes,
            'width' => $width,
            'height' => $height,
            'title' => $title,
            'alt_text' => 'Generated fallback image',
            'caption' => $sourceLabel,
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Find the best existing Media Library image for page/business context.
     *
     * Returns a URL when a likely match is found, otherwise null.
     */
    public function findContextualReplacementUrl(array $context = []): ?string
    {
        $subjectKeywords = $this->subjectKeywordsFromContext($context);
        $businessKeywords = $this->businessKeywordsFromContext($context);
        $excluded = $this->buildExclusionMap($context);

        $query = MediaFile::query()
            ->where('mime_type', 'like', 'image/%')
            ->whereNull('deleted_at')
            ->latest('id');

        if ($this->userId !== null) {
            $query->where('created_by', $this->userId);
        }

        $candidates = $query->limit(240)->get();

        if ($candidates->isEmpty() && $this->userId !== null) {
            $candidates = MediaFile::query()
                ->where('mime_type', 'like', 'image/%')
                ->whereNull('deleted_at')
                ->latest('id')
                ->limit(240)
                ->get();
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $best = null;
        $bestScore = -1;

        foreach ($candidates as $candidate) {
            $candidateUrl = (string) ($candidate->url ?? '');
            $text = strtolower(trim(implode(' ', [
                (string) ($candidate->title ?? ''),
                (string) ($candidate->alt_text ?? ''),
                (string) ($candidate->caption ?? ''),
                (string) ($candidate->filename ?? ''),
                (string) ($candidate->original_name ?? ''),
                (string) ($candidate->path ?? ''),
            ])));

            $score = 0;

            foreach ($subjectKeywords as $kw) {
                if ($kw !== '' && str_contains($text, $kw)) {
                    $score += 7;
                }
            }

            foreach ($businessKeywords as $kw) {
                if ($kw !== '' && str_contains($text, $kw)) {
                    $score += 5;
                }
            }

            $width = (int) ($candidate->width ?? 0);
            $height = (int) ($candidate->height ?? 0);
            if ($width > 0 && $height > 0 && $width >= $height) {
                $score += 2;
            }

            // Favor visual diversity by penalizing URLs already used in this clone run.
            if ($candidateUrl !== '' && isset($excluded[$candidateUrl])) {
                $score -= 40;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        if (!$best instanceof MediaFile) {
            return null;
        }

        // If we have no textual match, only accept a fallback candidate that is likely hero-capable.
        if ($bestScore <= 0) {
            $w = (int) ($best->width ?? 0);
            $h = (int) ($best->height ?? 0);
            if (!($w >= 1000 && $h >= 600 && $w >= $h)) {
                return null;
            }
        }

        return (string) $best->url;
    }

    /**
     * Build a map of media URLs to exclude from contextual replacement scoring,
     * preventing the same fallback image from being reused across multiple sections.
     *
     * @return array<string,true>
     */
    private function buildExclusionMap(array $context): array
    {
        $excluded = [];
        $list = $context['exclude_urls'] ?? [];
        if (!is_array($list)) {
            return $excluded;
        }

        foreach ($list as $url) {
            if (!is_string($url)) {
                continue;
            }
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            $excluded[$url] = true;
        }

        return $excluded;
    }

    /**
     * @return array{binary:string,mime_type:string,extension:string,source:string}
     */
    private function buildImageAsset(array $context): array
    {
        $prompt = $this->buildAiImagePrompt($context);
        $requireAi = (bool) ($context['require_ai'] ?? false);
        $disableAi = (bool) ($context['disable_ai'] ?? false);

        if (!$disableAi) {
            try {
                $img = $this->imageClient->generateImage($prompt, [
                    'size' => '1536x1024',
                ]);

                $binary = (string) ($img['binary'] ?? '');
                $mime = strtolower(trim((string) ($img['mime_type'] ?? 'image/png')));
                if ($binary !== '' && str_starts_with($mime, 'image/')) {
                    return [
                        'binary' => $binary,
                        'mime_type' => $mime,
                        'extension' => $this->extensionFromMime($mime),
                        'source' => 'ai',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('FallbackImageGenerator: AI image generation failed, trying free temporary image.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $freeAsset = $this->downloadFreeTemporaryImage();
        if ($freeAsset !== null) {
            return $freeAsset;
        }

        if ($requireAi) {
            Log::warning('FallbackImageGenerator: AI required but unavailable; using SVG fallback after free image retrieval failed.');
        }

        $svg = $this->buildSvg($context);
        return [
            'binary' => $svg,
            'mime_type' => 'image/svg+xml',
            'extension' => 'svg',
            'source' => 'static',
        ];
    }

    /**
     * Try downloading a free temporary stock image for fallback usage.
     *
     * @return array{binary:string,mime_type:string,extension:string,source:string}|null
     */
    private function downloadFreeTemporaryImage(): ?array
    {
        $candidates = [
            // No-key free placeholder/stock-like services.
            'https://picsum.photos/1536/1024',
            'https://placehold.co/1536x1024/jpg?text=Temporary+Image',
        ];

        foreach ($candidates as $url) {
            try {
                $resp = Http::timeout(15)
                    ->withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'ImpartCMS/1.0 (+clone-fallback)',
                        'Accept' => 'image/*,*/*;q=0.8',
                    ])
                    ->get($url);

                if (!$resp->successful()) {
                    continue;
                }

                $body = $resp->body();
                if (!is_string($body) || $body === '') {
                    continue;
                }

                $mime = strtolower(trim((string) ($resp->header('Content-Type') ?? 'image/jpeg')));
                if (!str_starts_with($mime, 'image/')) {
                    $mime = 'image/jpeg';
                }

                return [
                    'binary' => $body,
                    'mime_type' => $mime,
                    'extension' => $this->extensionFromMime($mime),
                    'source' => 'free',
                ];
            } catch (\Throwable $e) {
                Log::warning('FallbackImageGenerator: free temporary image download failed.', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function buildAiImagePrompt(array $context): string
    {
        $design = (array) ($context['design_system'] ?? []);
        $brand = $this->brandFromContext($context);
        $pageTitle = (string) ($context['page_title'] ?? '');
        $pageBody = (string) ($context['page_body'] ?? '');
        $originalSrc = (string) ($context['original_src'] ?? '');
        
        $primary = $this->safeHexColor((string) ($design['primary_color'] ?? '#2563eb'), '#2563eb');
        $secondary = $this->safeHexColor((string) ($design['secondary_color'] ?? '#0f172a'), '#0f172a');

        // Extract contextual clues from the page content
        $contextClues = $this->extractImageContext($pageTitle, $pageBody, $originalSrc, $brand);

        $parts = [];
        
        // Create specific, creative prompt based on context
        if ($contextClues['type'] === 'hero') {
            $parts[] = 'Create a stunning hero banner image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: modern, professional, high-impact, cinematic lighting.';
        } elseif ($contextClues['type'] === 'feature') {
            $parts[] = 'Create a feature section image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: clean, illustrative, modern, slightly abstract.';
        } elseif ($contextClues['type'] === 'team' || $contextClues['type'] === 'about') {
            $parts[] = 'Create a professional team/office environment image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: bright, collaborative, modern workspace, natural lighting.';
        } elseif ($contextClues['type'] === 'product') {
            $parts[] = 'Create a product showcase image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: clean product photography, minimal background, professional lighting.';
        } elseif ($contextClues['type'] === 'contact' || $contextClues['type'] === 'location') {
            $parts[] = 'Create a welcoming contact/location image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: inviting, professional, modern architecture or cityscape.';
        } else {
            $parts[] = 'Create a professional business website image:';
            $parts[] = $contextClues['description'];
            $parts[] = 'Style: modern, clean, professional, high quality.';
        }

        $parts[] = 'IMPORTANT: Photorealistic quality, NO placeholder text, NO watermarks, NO generic "image unavailable" graphics.';
        $parts[] = 'NO visible faces of people, NO brand logos, NO text overlays.';
        $parts[] = 'Color palette: incorporate ' . $primary . ' and ' . $secondary . ' tones naturally.';
        $parts[] = 'Landscape orientation (16:10 or similar). Ultra high quality, suitable for professional business website.';

        return implode(' ', $parts);
    }

    /**
     * Extract context from page content to generate specific image prompts.
     *
     * @return array{type:string,description:string}
     */
    private function extractImageContext(string $pageTitle, string $pageBody, string $originalSrc, string $brand): array
    {
        $combined = strtolower($pageTitle . ' ' . $pageBody . ' ' . $originalSrc);
        
        // Detect image type and create specific description
        if (preg_match('/\b(hero|banner|main|landing|welcome|home)\b/', $combined)) {
            $industries = $this->detectIndustry($combined, $brand);
            return [
                'type' => 'hero',
                'description' => "A hero image for a {$industries['industry']} business showing {$industries['imagery']}."
            ];
        }
        
        if (preg_match('/\b(team|about|staff|people|employee|our team|meet|who we are)\b/', $combined)) {
            return [
                'type' => 'team',
                'description' => 'Modern collaborative workspace with natural light, plants, and contemporary office design. Empty desks and workspace, no people visible.'
            ];
        }
        
        if (preg_match('/\b(contact|location|find us|visit|where|map|address|office|get in touch)\b/', $combined)) {
            return [
                'type' => 'contact',
                'description' => 'Modern business exterior or welcoming office entrance with glass facades, architectural details, and professional atmosphere.'
            ];
        }
        
        if (preg_match('/\b(product|item|shop|store|catalog|gallery|style)\b/', $combined)) {
            $industries = $this->detectIndustry($combined, $brand);
            return [
                'type' => 'product',
                'description' => "Professional product photography for {$industries['industry']}: {$industries['imagery']}."
            ];
        }
        
        if (preg_match('/\b(service|feature|what we do|solution|offer)\b/', $combined)) {
            $industries = $this->detectIndustry($combined, $brand);
            return [
                'type' => 'feature',
                'description' => "Abstract representation of {$industries['imagery']} in a modern, professional style."
            ];
        }
        
        // Default: generic but still specific business imagery
        $industries = $this->detectIndustry($combined, $brand);
        return [
            'type' => 'generic',
            'description' => "A professional {$industries['industry']} business image featuring {$industries['imagery']}."
        ];
    }

    /**
     * Detect industry and appropriate imagery based on content.
     *
     * @return array{industry:string,imagery:string}
     */
    private function detectIndustry(string $content, string $brand): array
    {
        $content = strtolower($content . ' ' . $brand);
        
        if (preg_match('/\b(restaurant|food|cafe|coffee|dining|culinary|chef|menu)\b/', $content)) {
            return ['industry' => 'restaurant/hospitality', 'imagery' => 'elegant table settings, ambient lighting, modern interior design'];
        }
        if (preg_match('/\b(tech|software|digital|app|cloud|data|cyber|innovation)\b/', $content)) {
            return ['industry' => 'technology', 'imagery' => 'sleek devices, abstract digital patterns, modern workspace with screens'];
        }
        if (preg_match('/\b(law|legal|attorney|lawyer|justice)\b/', $content)) {
            return ['industry' => 'legal', 'imagery' => 'modern law library, professional office space, architectural justice motifs'];
        }
        if (preg_match('/\b(medical|health|doctor|clinic|healthcare|wellness)\b/', $content)) {
            return ['industry' => 'healthcare', 'imagery' => 'clean medical environment, calming colors, modern healthcare facility'];
        }
        if (preg_match('/\b(real estate|property|home|house|apartment)\b/', $content)) {
            return ['industry' => 'real estate', 'imagery' => 'beautiful modern architecture, luxurious interior, stunning property views'];
        }
        if (preg_match('/\b(fashion|clothing|apparel|style|boutique|wear)\b/', $content)) {
            return ['industry' => 'fashion/retail', 'imagery' => 'elegant clothing displays, modern retail space, stylish fashion elements'];
        }
        if (preg_match('/\b(finance|banking|investment|accounting|financial)\b/', $content)) {
            return ['industry' => 'financial', 'imagery' => 'modern corporate office, professional business atmosphere, sleek architecture'];
        }
        if (preg_match('/\b(design|creative|agency|marketing|branding|studio)\b/', $content)) {
            return ['industry' => 'creative/agency', 'imagery' => 'colorful modern workspace, design tools, creative atmosphere with art'];
        }
        if (preg_match('/\b(construction|building|contractor|architecture)\b/', $content)) {
            return ['industry' => 'construction', 'imagery' => 'modern construction site, architectural blueprints, building progress'];
        }
        if (preg_match('/\b(education|school|learning|training|course)\b/', $content)) {
            return ['industry' => 'education', 'imagery' => 'modern classroom or learning space, books, bright educational environment'];
        }
        if (preg_match('/\b(fitness|gym|yoga|wellness|sport|exercise)\b/', $content)) {
            return ['industry' => 'fitness', 'imagery' => 'modern gym equipment, yoga studio, wellness space with natural elements'];
        }
        if (preg_match('/\b(auto|car|vehicle|automotive|garage)\b/', $content)) {
            return ['industry' => 'automotive', 'imagery' => 'sleek modern vehicles, professional showroom, automotive details'];
        }
        if (preg_match('/\b(travel|tour|vacation|hotel|hospitality)\b/', $content)) {
            return ['industry' => 'travel/hospitality', 'imagery' => 'beautiful destination views, luxury accommodation, travel-inspiring scenery'];
        }
        if (preg_match('/\b(blind|window|shade|curtain|automated|smart home)\b/', $content)) {
            return ['industry' => 'automated blinds/smart home', 'imagery' => 'modern window treatments, sleek automated blinds, contemporary interior with large windows'];
        }
        
        // Generic business
        return ['industry' => 'professional business', 'imagery' => 'modern office environment, professional workspace, contemporary business atmosphere'];
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'png',
        };
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function detectDimensions(string $path, string $mimeType): array
    {
        $mimeType = strtolower(trim($mimeType));
        if ($mimeType === 'image/svg+xml') {
            return [1200, 800];
        }

        try {
            $full = Storage::disk($this->disk)->path($path);
            $info = @getimagesize($full);
            if (is_array($info)) {
                return [
                    (int) ($info[0] ?? 0) ?: null,
                    (int) ($info[1] ?? 0) ?: null,
                ];
            }
        } catch (\Throwable $e) {
            // Fall through to null dimensions.
        }

        return [null, null];
    }

    private function buildSvg(array $context): string
    {
        $design = (array) ($context['design_system'] ?? []);
        $primary = $this->safeHexColor((string) ($design['primary_color'] ?? '#2563eb'), '#2563eb');
        $secondary = $this->safeHexColor((string) ($design['secondary_color'] ?? '#0f172a'), '#0f172a');
        $brand = $this->brandFromContext($context);
        $title = $brand !== '' ? $brand : 'Website';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="Image placeholder">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0%" stop-color="' . e($primary) . '"/>'
            . '<stop offset="100%" stop-color="' . e($secondary) . '"/>'
            . '</linearGradient></defs>'
            . '<rect width="1200" height="800" fill="url(#g)"/>'
            . '<rect x="140" y="120" width="920" height="560" rx="24" fill="rgba(255,255,255,0.14)"/>'
            . '<circle cx="420" cy="360" r="46" fill="rgba(255,255,255,0.85)"/>'
            . '<path d="M290 540c70-90 130-120 190-90 36 18 66 19 95-6 26-23 56-33 90-31 61 4 111 42 164 127H290z" fill="rgba(255,255,255,0.85)"/>'
            . '<text x="600" y="670" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" fill="#ffffff">'
            . e($title)
            . '</text>'
            . '<text x="600" y="715" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="rgba(255,255,255,0.92)">Image unavailable - generated fallback</text>'
            . '</svg>';
    }

    private function safeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
            return strtolower($value);
        }

        return $fallback;
    }

    private function brandFromContext(array $context): string
    {
        $design = (array) ($context['design_system'] ?? []);
        $candidate = trim((string) ($design['brand_name'] ?? ''));
        if ($candidate !== '') {
            return Str::limit($candidate, 40, '');
        }

        $sourceUrl = trim((string) ($context['source_url'] ?? ''));
        if ($sourceUrl !== '') {
            $host = parse_url($sourceUrl, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $host = preg_replace('/^www\./i', '', $host) ?? $host;
                return Str::limit($host, 40, '');
            }
        }

        return '';
    }

    /**
     * @return array<int,string>
     */
    private function subjectKeywordsFromContext(array $context): array
    {
        $text = strtolower(trim(implode(' ', [
            (string) ($context['page_title'] ?? ''),
            (string) ($context['page_body'] ?? ''),
            (string) ($context['original_src'] ?? ''),
        ])));

        $keywords = [];

        if (preg_match('/\b(hero|banner|landing|home|welcome)\b/', $text)) {
            $keywords = array_merge($keywords, ['hero', 'banner', 'homepage', 'landing']);
        }
        if (preg_match('/\b(team|about|staff|people|employee|leadership)\b/', $text)) {
            $keywords = array_merge($keywords, ['team', 'about', 'office', 'workspace']);
        }
        if (preg_match('/\b(contact|location|visit|address|map|office)\b/', $text)) {
            $keywords = array_merge($keywords, ['contact', 'location', 'office', 'building']);
        }
        if (preg_match('/\b(product|shop|store|catalog|collection|item)\b/', $text)) {
            $keywords = array_merge($keywords, ['product', 'shop', 'catalog', 'collection']);
        }
        if (preg_match('/\b(service|solution|offer|feature|what we do)\b/', $text)) {
            $keywords = array_merge($keywords, ['service', 'solution', 'feature', 'business']);
        }

        if ($keywords === []) {
            $keywords = ['business', 'website', 'hero'];
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @return array<int,string>
     */
    private function businessKeywordsFromContext(array $context): array
    {
        $brand = strtolower($this->brandFromContext($context));
        $text = strtolower(trim(implode(' ', [
            $brand,
            (string) ($context['page_title'] ?? ''),
            (string) ($context['page_body'] ?? ''),
            (string) ($context['source_url'] ?? ''),
        ])));

        if (preg_match('/\b(restaurant|food|cafe|coffee|dining|chef|menu)\b/', $text)) {
            return ['food', 'restaurant', 'dining', 'hospitality'];
        }
        if (preg_match('/\b(tech|software|digital|app|cloud|saas|data|cyber)\b/', $text)) {
            return ['technology', 'software', 'digital', 'app'];
        }
        if (preg_match('/\b(law|legal|attorney|lawyer|justice)\b/', $text)) {
            return ['legal', 'law', 'attorney', 'justice'];
        }
        if (preg_match('/\b(medical|health|doctor|clinic|healthcare|wellness)\b/', $text)) {
            return ['medical', 'health', 'clinic', 'healthcare'];
        }
        if (preg_match('/\b(real estate|property|home|house|apartment)\b/', $text)) {
            return ['real-estate', 'property', 'home', 'architecture'];
        }
        if (preg_match('/\b(fashion|clothing|apparel|style|boutique)\b/', $text)) {
            return ['fashion', 'style', 'retail', 'boutique'];
        }
        if (preg_match('/\b(finance|banking|investment|accounting|financial)\b/', $text)) {
            return ['finance', 'financial', 'banking', 'corporate'];
        }
        if (preg_match('/\b(construction|building|contractor|architecture)\b/', $text)) {
            return ['construction', 'building', 'architecture', 'project'];
        }
        if (preg_match('/\b(blind|window|shade|curtain|automated|smart home)\b/', $text)) {
            return ['window', 'interior', 'smart-home', 'blinds'];
        }

        return ['business', 'professional'];
    }
}
