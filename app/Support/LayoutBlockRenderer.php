<?php

namespace App\Support;

use App\Models\LayoutBlock;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class LayoutBlockRenderer
{
    public static function headerEnabled(): bool
    {
        // New key (preferred)
        $v = (string) (Setting::get('layout_header_enabled', null) ?? '');
        if ($v !== '') {
            return $v === '1';
        }
        // Backward compatible fallback
        return ((string) (Setting::get('front_header_enabled', '0') ?? '0')) === '1';
    }

    public static function footerEnabled(): bool
    {
        $v = (string) (Setting::get('layout_footer_enabled', null) ?? '');
        if ($v !== '') {
            return $v === '1';
        }
        return ((string) (Setting::get('front_footer_enabled', '0') ?? '0')) === '1';
    }

    public static function headerRaw(?Page $page = null): string
    {
        if (!self::headerEnabled()) return '';
        return (string) (self::resolveForPage('header', $page)?->content ?? '');
    }

    public static function footerRaw(?Page $page = null): string
    {
        if (!self::footerEnabled()) return '';
        return (string) (self::resolveForPage('footer', $page)?->content ?? '');
    }

    public static function resolvedHeaderId(?Page $page = null): ?int
    {
        $b = self::resolveForPage('header', $page);
        return $b ? (int) $b->id : null;
    }

    public static function resolvedFooterId(?Page $page = null): ?int
    {
        $b = self::resolveForPage('footer', $page);
        return $b ? (int) $b->id : null;
    }

    /**
     * Resolve the best matching block for a page:
     * 1) Page-level override (header_block_id/footer_block_id)
     * 2) First enabled matching block by priority asc, then id asc
     */
    private static function resolveForPage(string $type, ?Page $page = null): ?LayoutBlock
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['header', 'footer'], true)) return null;

        $pageId = $page?->id ? (int) $page->id : null;

        // 1) Per-page override
        if ($page && $pageId) {
            $overrideId = null;
            if ($type === 'header') {
                $overrideId = (int) ($page->header_block_id ?? 0);
            } else {
                $overrideId = (int) ($page->footer_block_id ?? 0);
            }

            if ($overrideId > 0) {
                $b = LayoutBlock::query()
                    ->whereKey($overrideId)
                    ->where('type', $type)
                    ->where('is_enabled', true)
                    ->first();

                if ($b) return $b;
            }
        }

        // 2) Global / targeted blocks (cached snapshot)
        $best = null;
        $bestSpecificity = -1;
        $bestPriority = PHP_INT_MAX;
        $bestId = PHP_INT_MAX;

        foreach (self::blocks() as $b) {
            if (($b['type'] ?? '') !== $type) continue;
            if (!self::appliesToPage($b, $pageId)) continue;

            $mode = (string) ($b['target_mode'] ?? 'global');
            $specificity = match ($mode) {
                'only' => 3,
                'except' => 2,
                default => 1,
            };

            $priority = (int) ($b['priority'] ?? 100);
            $id = (int) ($b['id'] ?? 0);

            $isBetter = false;
            if ($specificity > $bestSpecificity) {
                $isBetter = true;
            } elseif ($specificity === $bestSpecificity) {
                if ($priority < $bestPriority) {
                    $isBetter = true;
                } elseif ($priority === $bestPriority && $id < $bestId) {
                    $isBetter = true;
                }
            }

            if ($isBetter) {
                $best = $b;
                $bestSpecificity = $specificity;
                $bestPriority = $priority;
                $bestId = $id;
            }
        }

        if ($best) {
            $model = new LayoutBlock();
            $model->forceFill([
                'id' => (int) $best['id'],
                'type' => (string) $best['type'],
                'name' => (string) $best['name'],
                'is_enabled' => true,
                'target_mode' => (string) $best['target_mode'],
                'priority' => (int) $best['priority'],
                'content' => (string) $best['content'],
            ]);
            $model->exists = true;
            return $model;
        }

        return null;
    }

    /**
     * Cached snapshot of enabled blocks + their targeted page IDs.
     * Ordered by priority then id so "first match wins".
     *
     * @return array<int, array<string, mixed>>
     */
    private static function blocks(): array
    {
        return Cache::remember('layout_blocks:enabled:v1', 3600, function () {
            return LayoutBlock::query()
                ->where('is_enabled', true)
                ->orderBy('priority')
                ->orderBy('id')
                ->with('pages:id')
                ->get()
                ->map(function (LayoutBlock $b) {
                    return [
                        'id' => (int) $b->id,
                        'type' => (string) $b->type,
                        'name' => (string) $b->name,
                        'target_mode' => (string) ($b->target_mode ?? 'global'),
                        'priority' => (int) ($b->priority ?? 100),
                        'content' => (string) ($b->content ?? ''),
                        'page_ids' => $b->pages->pluck('id')->map(fn ($v) => (int) $v)->values()->all(),
                    ];
                })
                ->all();
        });
    }

    private static function appliesToPage(array $block, ?int $pageId): bool
    {
        $mode = strtolower((string) ($block['target_mode'] ?? 'global'));
        $ids = array_map('intval', (array) ($block['page_ids'] ?? []));

        // If we don't have a page ID, only global blocks apply.
        if (!$pageId) {
            return $mode === 'global';
        }

        if ($mode === 'global') return true;
        if ($mode === 'only') return in_array((int) $pageId, $ids, true);
        if ($mode === 'except') return !in_array((int) $pageId, $ids, true);

        return true;
    }
}
