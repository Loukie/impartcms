<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiAgentSettingsController extends Controller
{
    public function edit(): View
    {
        $provider = (string) (Setting::get('ai.provider', '') ?? '');
        $provider = $provider !== '' ? $provider : 'openai';
        $aiImagesEnabled = $this->toBool(Setting::get('ai.images.enabled', '0'));
        $cloneDesignMode = (string) (Setting::get('ai.clone.design_mode', 'premium') ?? 'premium');
        if (!in_array($cloneDesignMode, ['safe', 'premium', 'strict_reference'], true)) {
            $cloneDesignMode = 'premium';
        }
        $cloneBrandPrimaryColor = (string) (Setting::get('ai.clone.brand_primary_color', '') ?? '');
        $cloneBrandSecondaryColor = (string) (Setting::get('ai.clone.brand_secondary_color', '') ?? '');
        $cloneBrandAccentColor = (string) (Setting::get('ai.clone.brand_accent_color', '') ?? '');
        $cloneGlobalFont = (string) (Setting::get('ai.clone.global_font', '') ?? '');
        $cloneEnforceBrandTokens = $this->toBool(Setting::get('ai.clone.enforce_brand_tokens', '0'));

        // ---- Model options (curated) ----
        $openAiModelOptions = [
            ['id' => 'gpt-5.4-thinking', 'label' => 'gpt-5.4-thinking (GPT-5.4 Thinking)'],
            ['id' => 'gpt-4o', 'label' => 'gpt-4o (latest, recommended)'],
            ['id' => 'gpt-4-turbo', 'label' => 'gpt-4-turbo (strong, fast)'],
            ['id' => 'gpt-4', 'label' => 'gpt-4 (powerful, older)'],
            ['id' => 'gpt-3.5-turbo', 'label' => 'gpt-3.5-turbo (fast, cheap)'],
        ];

        $geminiModelOptions = [
            ['id' => 'gemini-2.5-flash-lite', 'label' => 'gemini-2.5-flash-lite (free-tier available)'],
            ['id' => 'gemini-2.5-flash', 'label' => 'gemini-2.5-flash (free-tier available)'],
            ['id' => 'gemini-2.5-pro', 'label' => 'gemini-2.5-pro (free-tier available)'],
            ['id' => 'gemini-2.0-flash', 'label' => 'gemini-2.0-flash (free-tier available)'],
            ['id' => 'gemini-2.5-flash-lite-preview-09-2025', 'label' => 'gemini-2.5-flash-lite-preview-09-2025 (preview)'],
            ['id' => 'gemini-flash-latest', 'label' => 'gemini-flash-latest (rolling)'],
        ];

        $anthropicModelOptions = [
            ['id' => 'claude-3-5-sonnet-latest', 'label' => 'claude-3-5-sonnet-latest'],
            ['id' => 'claude-3-5-haiku-latest', 'label' => 'claude-3-5-haiku-latest'],
            ['id' => 'claude-3-opus-latest', 'label' => 'claude-3-opus-latest'],
        ];

        // ---- OpenAI ----
        $openAiModel = (string) (Setting::get('ai.openai.model', env('OPENAI_MODEL', 'gpt-4o')) ?? 'gpt-4o');
        // Normalize any invalid models to gpt-4o
        $openAiModel = $this->normalizeOpenAiModel($openAiModel);
        
        // zero or missing means "no limit" (use HTTP default)
        $openAiTimeout = (int) (Setting::get('ai.openai.timeout', (int) (env('OPENAI_TIMEOUT', 0) ?? 0)) ?? 0);
        if ($openAiTimeout < 0) {
            $openAiTimeout = 0;
        }
        if ($openAiTimeout > 600) {
            // practical ceiling to avoid unbounded numbers
            $openAiTimeout = 600;
        }
        // display blank when using default/max behaviour
        $openAiTimeoutDisplay = $openAiTimeout > 0 ? $openAiTimeout : '';

        $openAiHasKey = trim(Setting::getSecret('ai.openai.api_key', '')) !== '' || trim((string) (env('OPENAI_API_KEY', '') ?? '')) !== '';

        [$openAiModelSelect, $openAiModelCustom] = $this->splitModelChoice($openAiModel, $openAiModelOptions);

        // ---- Anthropic (still coming soon) ----
        $anthropicModel = (string) (Setting::get('ai.anthropic.model', 'claude-3-5-sonnet-latest') ?? 'claude-3-5-sonnet-latest');
        $anthropicHasKey = trim(Setting::getSecret('ai.anthropic.api_key', '')) !== '';

        [$anthropicModelSelect, $anthropicModelCustom] = $this->splitModelChoice($anthropicModel, $anthropicModelOptions);

        // ---- Gemini ----
        $geminiModel = (string) (Setting::get('ai.gemini.model', 'gemini-2.5-flash-lite') ?? 'gemini-2.5-flash-lite');
        $geminiTimeout = (int) (Setting::get('ai.gemini.timeout', (int) (env('GEMINI_TIMEOUT', 0) ?? 0)) ?? 0);
        if ($geminiTimeout < 0) {
            $geminiTimeout = 0;
        }
        if ($geminiTimeout > 600) {
            $geminiTimeout = 600;
        }
        $geminiTimeoutDisplay = $geminiTimeout > 0 ? $geminiTimeout : '';
        $geminiHasKey = trim(Setting::getSecret('ai.gemini.api_key', '')) !== '';

        [$geminiModelSelect, $geminiModelCustom] = $this->splitModelChoice($geminiModel, $geminiModelOptions);

        return view('admin.settings.ai-agent', [
            'provider' => $provider,
            'aiImagesEnabled' => $aiImagesEnabled,
            'cloneDesignMode' => $cloneDesignMode,
            'cloneBrandPrimaryColor' => $cloneBrandPrimaryColor,
            'cloneBrandSecondaryColor' => $cloneBrandSecondaryColor,
            'cloneBrandAccentColor' => $cloneBrandAccentColor,
            'cloneGlobalFont' => $cloneGlobalFont,
            'cloneEnforceBrandTokens' => $cloneEnforceBrandTokens,

            'openAiModelOptions' => $openAiModelOptions,
            'openAiModel' => $openAiModel,
            'openAiModelSelect' => $openAiModelSelect,
            'openAiModelCustom' => $openAiModelCustom,
            'openAiTimeout' => $openAiTimeout,
            'openAiTimeoutDisplay' => $openAiTimeoutDisplay,
            'openAiHasKey' => $openAiHasKey,

            'anthropicModelOptions' => $anthropicModelOptions,
            'anthropicModel' => $anthropicModel,
            'anthropicModelSelect' => $anthropicModelSelect,
            'anthropicModelCustom' => $anthropicModelCustom,
            'anthropicHasKey' => $anthropicHasKey,

            'geminiModelOptions' => $geminiModelOptions,
            'geminiModel' => $geminiModel,
            'geminiModelSelect' => $geminiModelSelect,
            'geminiModelCustom' => $geminiModelCustom,
            'geminiTimeout' => $geminiTimeout,
            'geminiTimeoutDisplay' => $geminiTimeoutDisplay,
            'geminiHasKey' => $geminiHasKey,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['openai', 'anthropic', 'gemini', 'disabled'])],
            'ai_images_enabled' => ['nullable', 'boolean'],
            'clone_design_mode' => ['nullable', Rule::in(['safe', 'premium', 'strict_reference'])],
            'clone_brand_primary_color' => ['nullable', 'string', 'max:16'],
            'clone_brand_secondary_color' => ['nullable', 'string', 'max:16'],
            'clone_brand_accent_color' => ['nullable', 'string', 'max:16'],
            'clone_global_font' => ['nullable', 'string', 'max:180'],
            'clone_enforce_brand_tokens' => ['nullable', 'boolean'],

            // OpenAI
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'openai_api_key_clear' => ['nullable', 'boolean'],
            'openai_model' => ['nullable', 'string', 'max:120'],
            'openai_timeout' => ['nullable', 'integer', 'min:0', 'max:600'],

            // Anthropic (future)
            'anthropic_api_key' => ['nullable', 'string', 'max:500'],
            'anthropic_api_key_clear' => ['nullable', 'boolean'],
            'anthropic_model' => ['nullable', 'string', 'max:120'],

            // Gemini
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'gemini_api_key_clear' => ['nullable', 'boolean'],
            'gemini_model' => ['nullable', 'string', 'max:120'],
            'gemini_timeout' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        Setting::set('ai.provider', (string) $data['provider']);
        Setting::set('ai.images.enabled', ((bool) ($data['ai_images_enabled'] ?? false)) ? '1' : '0');
        Setting::set('ai.clone.design_mode', (string) ($data['clone_design_mode'] ?? 'premium'));

        $primary = trim((string) ($data['clone_brand_primary_color'] ?? ''));
        $secondary = trim((string) ($data['clone_brand_secondary_color'] ?? ''));
        $accent = trim((string) ($data['clone_brand_accent_color'] ?? ''));
        $font = trim((string) ($data['clone_global_font'] ?? ''));

        Setting::set('ai.clone.brand_primary_color', $this->normalizeOptionalHex($primary));
        Setting::set('ai.clone.brand_secondary_color', $this->normalizeOptionalHex($secondary));
        Setting::set('ai.clone.brand_accent_color', $this->normalizeOptionalHex($accent));
        Setting::set('ai.clone.global_font', $font);
        Setting::set('ai.clone.enforce_brand_tokens', ((bool) ($data['clone_enforce_brand_tokens'] ?? false)) ? '1' : '0');

        // OpenAI settings (stored even if provider not active)
        // Only update key if explicitly cleared or if a new value is provided
        $openaiClear = (bool) ($data['openai_api_key_clear'] ?? false);
        if ($openaiClear) {
            Setting::setSecret('ai.openai.api_key', null);
        } elseif (array_key_exists('openai_api_key', $data) && trim((string) ($data['openai_api_key'] ?? '')) !== '') {
            // Only save if field is non-empty (new key entered)
            Setting::setSecret('ai.openai.api_key', (string) $data['openai_api_key']);
        }
        // If field is empty and not cleared, we leave existing key untouched

        $openAiModel = trim((string) ($data['openai_model'] ?? ''));
        if ($openAiModel !== '') {
            // Normalize the model name before saving
            $openAiModel = $this->normalizeOpenAiModel($openAiModel);
            Setting::set('ai.openai.model', $openAiModel);
        }

        if (array_key_exists('openai_timeout', $data)) {
            $val = $data['openai_timeout'];
            Setting::set('ai.openai.timeout', (string) ((int) ($val ?? 0)));
        }

        // Anthropic settings (stored even if provider not active)
        // Only update key if explicitly cleared or if a new value is provided
        $anthropicClear = (bool) ($data['anthropic_api_key_clear'] ?? false);
        if ($anthropicClear) {
            Setting::setSecret('ai.anthropic.api_key', null);
        } elseif (array_key_exists('anthropic_api_key', $data) && trim((string) ($data['anthropic_api_key'] ?? '')) !== '') {
            // Only save if field is non-empty (new key entered)
            Setting::setSecret('ai.anthropic.api_key', (string) $data['anthropic_api_key']);
        }
        // If field is empty and not cleared, we leave existing key untouched

        $anthropicModel = trim((string) ($data['anthropic_model'] ?? ''));
        if ($anthropicModel !== '') {
            Setting::set('ai.anthropic.model', $anthropicModel);
        }

        // Gemini settings (stored even if provider not active)
        // Only update key if explicitly cleared or if a new value is provided
        $geminiClear = (bool) ($data['gemini_api_key_clear'] ?? false);
        if ($geminiClear) {
            Setting::setSecret('ai.gemini.api_key', null);
        } elseif (array_key_exists('gemini_api_key', $data) && trim((string) ($data['gemini_api_key'] ?? '')) !== '') {
            // Only save if field is non-empty (new key entered)
            Setting::setSecret('ai.gemini.api_key', (string) $data['gemini_api_key']);
        }
        // If field is empty and not cleared, we leave existing key untouched

        $geminiModel = trim((string) ($data['gemini_model'] ?? ''));
        if ($geminiModel !== '') {
            Setting::set('ai.gemini.model', $geminiModel);
        }

        if (array_key_exists('gemini_timeout', $data)) {
            $val = $data['gemini_timeout'];
            Setting::set('ai.gemini.timeout', (string) ((int) ($val ?? 0)));
        }

        return back()->with('status', 'AI Agent settings saved ✅');
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $str = strtolower(trim((string) $value));
        return in_array($str, ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeOptionalHex(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1) {
            return strtolower($value);
        }

        return '';
    }

    /**
     * @param array<int,array{id:string,label:string}> $options
     * @return array{0:string,1:string} [selectValue, customValue]
     */
    private function splitModelChoice(string $current, array $options): array
    {
        $ids = [];
        foreach ($options as $opt) {
            if (isset($opt['id']) && is_string($opt['id'])) {
                $ids[] = $opt['id'];
            }
        }

        if (in_array($current, $ids, true)) {
            return [$current, ''];
        }

        return ['custom', $current];
    }

    /**
     * Normalize OpenAI model names and accept supported aliases.
     */
    private function normalizeOpenAiModel(string $model): string
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return 'gpt-4o';
        }

        // Accept friendly/legacy variants for GPT-5.4 Thinking.
        if (in_array($model, ['gpt-5.4', 'gpt-5.4 thinking', 'gpt54-thinking'], true)) {
            return 'gpt-5.4-thinking';
        }

        // Valid OpenAI models
        $validModels = [
            'gpt-5.4-thinking',
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo',
        ];

        if (in_array($model, $validModels, true)) {
            return $model;
        }

        // Default fallback
        return 'gpt-4o';
    }
}
