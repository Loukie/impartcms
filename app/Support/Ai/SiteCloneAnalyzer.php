<?php

namespace App\Support\Ai;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class SiteCloneAnalyzer
{
    public function __construct(
        private readonly int $timeoutSeconds = 30,
    ) {}

    /**
     * Analyze a website by URL and extract structure/content.
     *
     * @return array{
     *   url: string,
     *   title: string,
     *   pages: array<int, array{
     *     url: string,
     *     title: string,
     *     description: string,
     *     headings: array<string>,
     *     content_sample: string,
     *   }>,
     *   navigation: array<string>,
     *   colors: array<string>,
     *   fonts: array<string>,
     * }
     */
    public function analyze(string $url, int $maxPages = 8): array
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL provided.');
        }

        // Ensure URL has protocol
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        try {
            $html = $this->fetchUrl($url);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch URL: ' . $e->getMessage());
        }

        $crawler = new Crawler($html);

        // Extract site title
        $siteTitle = $this->extractTitle($crawler);

        // Extract navigation links
        $navLinks = $this->extractNavigation($crawler, $url);

        // Extract page list (from links)
        $pages = $this->extractPages($crawler, $url, array_slice($navLinks, 0, $maxPages));

        // Extract design elements
        $colors = $this->extractColors($crawler, $url);
        $fonts = $this->extractFonts($crawler);
        $images = $this->extractImages($crawler, $url);
        $videos = $this->extractVideos($crawler, $url);

        return [
            'url' => $url,
            'title' => $siteTitle,
            'pages' => $pages,
            'navigation' => $navLinks,
            'colors' => $colors,
            'fonts' => $fonts,
            'images' => $images,
            'videos' => $videos,
        ];
    }

    private function fetchUrl(string $url): string
    {
        try {
            \Log::info('SiteCloneAnalyzer: Fetching URL', ['url' => $url]);
            
            // Build request with comprehensive headers
            $response = Http::withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'DNT' => '1',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ])
                ->timeout($this->timeoutSeconds)
                ->retry(2, 500)
                ->withoutVerifying()  // Skip SSL verification (common in local dev)
                ->get($url);

            $response->throw();
            
            \Log::info('SiteCloneAnalyzer: Request successful', [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('SiteCloneAnalyzer: Connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not connect to website. Check that the URL is correct and the website is publicly accessible. The server may not have internet access or be blocked by a firewall. Details: ' . $e->getMessage());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $statusCode = $e->response?->status() ?? 'unknown';
            \Log::error('SiteCloneAnalyzer: Request failed', [
                'url' => $url,
                'status' => $statusCode,
                'error' => $e->getMessage(),
            ]);
            
            if ($statusCode === 403) {
                throw new \RuntimeException('Website blocked the request (403 Forbidden). The site may not allow automated access.');
            } elseif ($statusCode === 404) {
                throw new \RuntimeException('Website not found (404). Please verify the URL is correct.');
            } elseif ($statusCode >= 500) {
                throw new \RuntimeException('Website returned a server error (' . $statusCode . '). Please try again later.');
            }
            
            throw new \RuntimeException('Failed to fetch URL (HTTP ' . $statusCode . '): ' . $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('SiteCloneAnalyzer: Unexpected error', [
                'url' => $url,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            throw new \RuntimeException('Failed to fetch URL: ' . $e->getMessage());
        }

        $body = (string) $response->body();
        if (trim($body) === '') {
            throw new \RuntimeException('Website returned empty content.');
        }

        // Validate that we got HTML, not an error page
        $contentType = $response->header('Content-Type') ?? '';
        if (stripos($contentType, 'text/html') === false && 
            !preg_match('/<html|<!doctype/i', substr($body, 0, 500))) {
            throw new \RuntimeException('Website did not return valid HTML content.');
        }

        return $body;
    }

    private function extractTitle(Crawler $crawler): string
    {
        try {
            $title = $crawler->filterXPath('//title')->text();
            return trim($title) ?: 'Website';
        } catch (\Throwable $e) {
            return 'Website';
        }
    }

    private function extractNavigation(Crawler $crawler, string $baseUrl): array
    {
        $nav = [];

        try {
            // Look for nav links
            $crawler->filterXPath('//nav//a | //header//a | //ul[contains(@class, "nav")]//a')
                ->each(function (Crawler $node) use (&$nav) {
                    $text = trim($node->text());
                    if ($text !== '' && strlen($text) < 50) {
                        $nav[] = $text;
                    }
                });
        } catch (\Throwable $e) {
            // Continue
        }

        // Remove duplicates and limit
        $nav = array_unique($nav);
        return array_slice(array_values($nav), 0, 10);
    }

    private function extractPages(Crawler $crawler, string $baseUrl, array $navTitles): array
    {
        $pages = [];
        $parsedBase = parse_url($baseUrl);
        $baseHost = $parsedBase['host'] ?? '';

        try {
            $crawler->filterXPath('//a[@href]')->each(function (Crawler $node) use (
                &$pages,
                $baseUrl,
                $baseHost
            ) {
                $href = (string) $node->attr('href');
                if (!$this->isValidPageUrl($href, $baseHost)) {
                    return;
                }

                $absoluteUrl = $this->resolveUrl($href, $baseUrl);
                if (isset($pages[$absoluteUrl])) {
                    return; // Already found this page
                }

                if (count($pages) >= 8) {
                    return; // Stop at max pages
                }

                $title = trim($node->text()) ?: $this->slugToTitle($href);

                try {
                    $pageHtml = $this->fetchUrl($absoluteUrl);
                    $pageCrawler = new Crawler($pageHtml);

                    $description = $this->extractMetaDescription($pageCrawler);
                    $headings = $this->extractHeadings($pageCrawler);
                    $contentSample = $this->extractContentSample($pageCrawler);

                    $pages[$absoluteUrl] = [
                        'url' => $absoluteUrl,
                        'title' => $title,
                        'description' => $description,
                        'headings' => $headings,
                        'content_sample' => $contentSample,
                    ];
                } catch (\Throwable $e) {
                    // Skip pages that fail to fetch
                }
            });
        } catch (\Throwable $e) {
            // Continue with what we have
        }

        return array_values($pages);
    }

    private function isValidPageUrl(string $href, string $baseHost): bool
    {
        // Skip anchors, javascript, external links
        if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:') ||
            str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            return false;
        }

        // Only same-host links
        if (str_starts_with($href, 'http')) {
            $parsed = parse_url($href);
            if (($parsed['host'] ?? '') !== $baseHost) {
                return false;
            }
        }

        return true;
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (str_starts_with($href, 'http')) {
            return $href;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $href;
        }

        $path = dirname($parsed['path'] ?? '/');
        if ($path !== '/' && !str_ends_with($path, '/')) {
            $path .= '/';
        }

        return $scheme . '://' . $host . $path . $href;
    }

    private function extractMetaDescription(Crawler $crawler): string
    {
        try {
            $node = $crawler->filterXPath('//meta[@name="description"]')->first();
            return trim((string) $node->attr('content')) ?: '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractHeadings(Crawler $crawler): array
    {
        $headings = [];
        try {
            $crawler->filterXPath('//h1 | //h2')->slice(0, 3)->each(function (Crawler $node) use (&$headings) {
                $text = trim($node->text());
                if ($text !== '' && strlen($text) < 100) {
                    $headings[] = $text;
                }
            });
        } catch (\Throwable $e) {
            // Continue
        }

        return $headings;
    }

    private function extractContentSample(Crawler $crawler): string
    {
        try {
            $main = $crawler->filterXPath('//main | //article | //*[@role="main"]')->first();
            if ($main->count() === 0) {
                $main = $crawler->filterXPath('//body')->first();
            }

            $text = trim($main->text());
            // Extract first 300 chars
            return mb_substr($text, 0, 300) . (mb_strlen($text) > 300 ? '...' : '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractColors(Crawler $crawler, string $baseUrl): array
    {
        $colors = [];

        // Extract colors from inline styles
        try {
            $crawler->filterXPath('//*[@style]')->each(function (Crawler $node) use (&$colors) {
                $style = (string) $node->attr('style');
                if (preg_match_all('/#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3}|rgb\([^)]+\)|rgba\([^)]+\)/', $style, $matches)) {
                    $colors = array_merge($colors, $matches[0]);
                }
            });
        } catch (\Throwable $e) {
            // Continue
        }

        // Extract colors from <style> tags
        try {
            $crawler->filterXPath('//style')->each(function (Crawler $node) use (&$colors) {
                $css = (string) $node->text();
                if (preg_match_all('/#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3}|rgb\([^)]+\)|rgba\([^)]+\)/', $css, $matches)) {
                    $colors = array_merge($colors, $matches[0]);
                }
            });
        } catch (\Throwable $e) {
            // Continue
        }

        // Extract colors from external CSS files (NEW!)
        try {
            $crawler->filterXPath('//link[@rel="stylesheet"]')->each(function (Crawler $node) use (&$colors, $baseUrl) {
                $href = (string) $node->attr('href');
                if (trim($href) === '') {
                    return;
                }

                // Make URL absolute
                $cssUrl = $this->resolveUrl($href, $baseUrl);

                try {
                    \Log::info('Fetching external CSS', ['url' => $cssUrl]);
                    $response = \Illuminate\Support\Facades\Http::timeout(10)
                        ->withoutVerifying()
                        ->get($cssUrl);
                    
                    if ($response->successful()) {
                        $css = (string) $response->body();
                        if (preg_match_all('/#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3}|rgb\([^)]+\)|rgba\([^)]+\)/', $css, $matches)) {
                            $colors = array_merge($colors, $matches[0]);
                            \Log::info('Found colors in CSS', ['count' => count($matches[0]), 'url' => $cssUrl]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::debug('Failed to fetch CSS file', ['url' => $cssUrl, 'error' => $e->getMessage()]);
                    // Continue with other files
                }
            });
        } catch (\Throwable $e) {
            // Continue
        }

        // Normalize hex colors to 6-digit format and uppercase
        $colors = array_map(function($color) {
            // Expand 3-digit hex to 6-digit (#e09 -> #EE0099)
            if (preg_match('/#([0-9a-fA-F]{3})$/i', $color, $m)) {
                return strtoupper('#' . implode('', array_map(fn($c) => $c.$c, str_split($m[1]))));
            }
            // Normalize to uppercase
            if (preg_match('/#[0-9a-fA-F]{6}/i', $color)) {
                return strtoupper($color);
            }
            return $color;
        }, $colors);

        // Count color frequency
        $colorCounts = array_count_values($colors);
        arsort($colorCounts);  // Sort by frequency (most common first)

        // Filter out very light/dark colors that aren't meaningful
        $colorCounts = array_filter($colorCounts, function($count, $color) {
            // Remove very light (#f0f0f0+) or very dark (#1a1a1a-) unless they're meaningful
            if (preg_match('/#([0-9a-fA-F]{6})/i', $color, $m)) {
                $hex = $m[1];
                // Convert to RGB and check if it's nearly white or nearly black
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                
                // Skip if it's basically white (>245) or black (<10)
                if (($r > 245 && $g > 245 && $b > 245) || ($r < 10 && $g < 10 && $b < 10)) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);

        // Return top colors by frequency (up to 12)
        return array_slice(array_keys($colorCounts), 0, 12);
    }

    private function extractFonts(Crawler $crawler): array
    {
        $fonts = [];

        try {
            // Look for Google Fonts or font-family declarations
            $crawler->filterXPath('//link[@href]')->each(function (Crawler $node) use (&$fonts) {
                $href = (string) $node->attr('href');
                if (str_contains($href, 'fonts.googleapis.com')) {
                    if (preg_match('/family=([^&]+)/', $href, $matches)) {
                        $fontName = urldecode($matches[1]);
                        $fonts[] = $fontName;
                    }
                }
            });
        } catch (\Throwable $e) {
            // Continue
        }

        $fonts = array_unique($fonts);
        return array_slice(array_values($fonts), 0, 3);
    }

    private function extractImages(Crawler $crawler, string $baseUrl): array
    {
        $images = [
            'logo' => null,
            'hero' => [],
            'content' => [],
            'icons' => [],
        ];

        try {
            // Extract logo (header, footer, common logo classes/IDs)
            $logoSelectors = [
                '//header//img[contains(@class, "logo")]',
                '//img[contains(@class, "logo")]',
                '//img[contains(@id, "logo")]',
                '//a[contains(@class, "logo")]//img',
                '//header//img[1]',
            ];
            
            foreach ($logoSelectors as $selector) {
                try {
                    $logoNode = $crawler->filterXPath($selector)->first();
                    if ($logoNode->count() > 0) {
                        $logoSrc = $logoNode->attr('src');
                        if ($logoSrc) {
                            $images['logo'] = $this->resolveUrl($logoSrc, $baseUrl);
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            // Extract hero/banner images (large images, backgrounds, first slider images)
            $heroCount = 0;
            $crawler->filterXPath('//header//img | //section[1]//img | //*[contains(@class, "hero")]//img | //*[contains(@class, "banner")]//img | //*[contains(@class, "slider")]//img')->each(function (Crawler $node) use (&$images, $baseUrl, &$heroCount) {
                if ($heroCount >= 10) return;
                $src = $node->attr('src');
                if ($src && !in_array($this->resolveUrl($src, $baseUrl), $images['hero'])) {
                    $images['hero'][] = $this->resolveUrl($src, $baseUrl);
                    $heroCount++;
                }
            });

            // Extract content images (increased limit for better coverage)
            $contentCount = 0;
            $crawler->filterXPath('//main//img | //article//img | //*[@id="content"]//img | //section//img')->each(function (Crawler $node) use (&$images, $baseUrl, &$contentCount) {
                if ($contentCount >= 30) return;
                $src = $node->attr('src');
                if ($src) {
                    $resolvedUrl = $this->resolveUrl($src, $baseUrl);
                    if (!in_array($resolvedUrl, $images['content']) && !in_array($resolvedUrl, $images['hero'])) {
                        $images['content'][] = $resolvedUrl;
                        $contentCount++;
                    }
                }
            });

            // Extract icons (SVG, small images with "icon" in class/src)
            $iconCount = 0;
            $crawler->filterXPath('//img[contains(@class, "icon") or contains(@src, "icon")] | //svg')->each(function (Crawler $node) use (&$images, $baseUrl, &$iconCount) {
                if ($iconCount >= 8) return;
                if ($node->nodeName() === 'svg') {
                    try {
                        $images['icons'][] = ['type' => 'svg', 'html' => $node->html()];
                        $iconCount++;
                    } catch (\Throwable $e) {
                        // Continue
                    }
                } else {
                    $src = $node->attr('src');
                    if ($src) {
                        $images['icons'][] = ['type' => 'img', 'url' => $this->resolveUrl($src, $baseUrl)];
                        $iconCount++;
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::debug('Image extraction error', ['error' => $e->getMessage()]);
        }

        return $images;
    }

    private function extractVideos(Crawler $crawler, string $baseUrl): array
    {
        $videos = [];

        try {
            // Extract video elements
            $crawler->filterXPath('//video | //iframe[contains(@src, "youtube") or contains(@src, "vimeo")]')->each(function (Crawler $node) use (&$videos, $baseUrl) {
                if (count($videos) >= 5) return;
                
                if ($node->nodeName() === 'video') {
                    $src = $node->attr('src');
                    if ($src) {
                        $videos[] = [
                            'type' => 'video',
                            'url' => $this->resolveUrl($src, $baseUrl),
                        ];
                    }
                } elseif ($node->nodeName() === 'iframe') {
                    $src = $node->attr('src');
                    if ($src) {
                        $videos[] = [
                            'type' => 'embed',
                            'url' => $src,
                        ];
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::debug('Video extraction error', ['error' => $e->getMessage()]);
        }

        return $videos;
    }

    private function slugToTitle(string $slug): string
    {
        $slug = basename(parse_url($slug, PHP_URL_PATH) ?? '/');
        $slug = str_replace(['-', '_'], ' ', $slug);
        return ucwords(trim($slug)) ?: 'Page';
    }
}
