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

        // ---- Model options (curated) ----
        $openAiModelOptions = [
            ['id' => 'gpt-5.2', 'label' => 'gpt-5.2 (best overall)'],
            ['id' => 'gpt-5-mini', 'label' => 'gpt-5-mini (fast + cheaper)'],
            ['id' => 'gpt-5-nano', 'label' => 'gpt-5-nano (cheapest)'],
            ['id' => 'gpt-4.1', 'label' => 'gpt-4.1 (strong non-reasoning)'],
            ['id' => 'gpt-4.1-mini', 'label' => 'gpt-4.1-mini (fast + capable)'],
            ['id' => 'gpt-4.1-nano', 'label' => 'gpt-4.1-nano (very cheap)'],
            ['id' => 'gpt-4o', 'label' => 'gpt-4o (older omni)'],
            ['id' => 'gpt-4o-mini', 'label' => 'gpt-4o-mini (older cheap)'],
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
        $openAiModel = (string) (Setting::get('ai.openai.model', env('OPENAI_MODEL', 'gpt-5.2')) ?? 'gpt-5.2');
        $openAiTimeout = (int) (Setting::get('ai.openai.timeout', (int) (env('OPENAI_TIMEOUT', 60) ?? 60)) ?? 60);
        if ($openAiTimeout < 5) $openAiTimeout = 5;
        if ($openAiTimeout > 120) $openAiTimeout = 120;

        $openAiHasKey = trim(Setting::getSecret('ai.openai.api_key', '')) !== '' || trim((string) (env('OPENAI_API_KEY', '') ?? '')) !== '';

        [$openAiModelSelect, $openAiModelCustom] = $this->splitModelChoice($openAiModel, $openAiModelOptions);

        // ---- Anthropic (still coming soon) ----
        $anthropicModel = (string) (Setting::get('ai.anthropic.model', 'claude-3-5-sonnet-latest') ?? 'claude-3-5-sonnet-latest');
        $anthropicHasKey = trim(Setting::getSecret('ai.anthropic.api_key', '')) !== '';

        [$anthropicModelSelect, $anthropicModelCustom] = $this->splitModelChoice($anthropicModel, $anthropicModelOptions);

        // ---- Gemini ----
        $geminiModel = (string) (Setting::get('ai.gemini.model', 'gemini-2.5-flash-lite') ?? 'gemini-2.5-flash-lite');
        $geminiTimeout = (int) (Setting::get('ai.gemini.timeout', 60) ?? 60);
        if ($geminiTimeout < 5) $geminiTimeout = 5;
        if ($geminiTimeout > 120) $geminiTimeout = 120;
        $geminiHasKey = trim(Setting::getSecret('ai.gemini.api_key', '')) !== '';

        [$geminiModelSelect, $geminiModelCustom] = $this->splitModelChoice($geminiModel, $geminiModelOptions);

        return view('admin.settings.ai-agent', [
            'provider' => $provider,

            'openAiModelOptions' => $openAiModelOptions,
            'openAiModel' => $openAiModel,
            'openAiModelSelect' => $openAiModelSelect,
            'openAiModelCustom' => $openAiModelCustom,
            'openAiTimeout' => $openAiTimeout,
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
            'geminiHasKey' => $geminiHasKey,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['openai', 'anthropic', 'gemini', 'disabled'])],

            // OpenAI
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'openai_api_key_clear' => ['nullable', 'boolean'],
            'openai_model' => ['nullable', 'string', 'max:120'],
            'openai_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],

            // Anthropic (future)
            'anthropic_api_key' => ['nullable', 'string', 'max:500'],
            'anthropic_api_key_clear' => ['nullable', 'boolean'],
            'anthropic_model' => ['nullable', 'string', 'max:120'],

            // Gemini
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'gemini_api_key_clear' => ['nullable', 'boolean'],
            'gemini_model' => ['nullable', 'string', 'max:120'],
            'gemini_timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);

        Setting::set('ai.provider', (string) $data['provider']);

        // OpenAI settings
        $openaiClear = (bool) ($data['openai_api_key_clear'] ?? false);
        if ($openaiClear) {
            Setting::setSecret('ai.openai.api_key', null);
        } elseif (array_key_exists('openai_api_key', $data)) {
            Setting::setSecret('ai.openai.api_key', (string) ($data['openai_api_key'] ?? ''));
        }

        $openAiModel = trim((string) ($data['openai_model'] ?? ''));
        if ($openAiModel !== '') {
            Setting::set('ai.openai.model', $openAiModel);
        }

        if (array_key_exists('openai_timeout', $data) && $data['openai_timeout'] !== null) {
            Setting::set('ai.openai.timeout', (string) ((int) $data['openai_timeout']));
        }

        // Anthropic settings (stored even if provider not active)
        $anthropicClear = (bool) ($data['anthropic_api_key_clear'] ?? false);
        if ($anthropicClear) {
            Setting::setSecret('ai.anthropic.api_key', null);
        } elseif (array_key_exists('anthropic_api_key', $data)) {
            Setting::setSecret('ai.anthropic.api_key', (string) ($data['anthropic_api_key'] ?? ''));
        }
        $anthropicModel = trim((string) ($data['anthropic_model'] ?? ''));
        if ($anthropicModel !== '') {
            Setting::set('ai.anthropic.model', $anthropicModel);
        }

        // Gemini settings (stored even if provider not active)
        $geminiClear = (bool) ($data['gemini_api_key_clear'] ?? false);
        if ($geminiClear) {
            Setting::setSecret('ai.gemini.api_key', null);
        } elseif (array_key_exists('gemini_api_key', $data)) {
            Setting::setSecret('ai.gemini.api_key', (string) ($data['gemini_api_key'] ?? ''));
        }
        $geminiModel = trim((string) ($data['gemini_model'] ?? ''));
        if ($geminiModel !== '') {
            Setting::set('ai.gemini.model', $geminiModel);
        }

        if (array_key_exists('gemini_timeout', $data) && $data['gemini_timeout'] !== null) {
            Setting::set('ai.gemini.timeout', (string) ((int) $data['gemini_timeout']));
        }

        return back()->with('status', 'AI Agent settings saved ✅');
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
}
