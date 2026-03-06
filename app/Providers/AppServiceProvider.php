<?php

namespace App\Providers;

use App\Support\Ai\LlmClientInterface;
use App\Support\Ai\NullLlmClient;
use App\Support\Ai\OpenAiResponsesClient;
use App\Support\Ai\AnthropicClient;
use App\Support\Ai\GeminiGenerateContentClient;
use App\Support\Ai\VisionClientInterface;
use App\Support\Ai\NullVisionClient;
use App\Support\Ai\GeminiVisionClient;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AI (LLM) binding
        // Priority order:
        // 1) Admin-configured settings (DB) if available
        // 2) .env fallback
        // 3) Null client with a helpful error
        $this->app->bind(LlmClientInterface::class, function () {
            $settingsAvailable = false;
            try {
                $settingsAvailable = Schema::hasTable('settings');
            } catch (\Throwable $e) {
                $settingsAvailable = false;
            }

            $provider = '';
            if ($settingsAvailable) {
                try {
                    $provider = (string) (Setting::get('ai.provider', '') ?? '');
                } catch (\Throwable $e) {
                    $provider = '';
                }
            }
            $provider = strtolower(trim($provider));

            // If not explicitly set, infer from env.
            if ($provider === '') {
                $provider = trim((string) (env('AI_PROVIDER', '') ?? ''));
                $provider = strtolower($provider);
            }
            if ($provider === '') {
                $provider = trim((string) (env('OPENAI_API_KEY', '') ?? '')) !== '' ? 'openai' : 'disabled';
            }

            if ($provider === 'disabled') {
                return new NullLlmClient('AI is disabled. Enable it in Admin → AI Agent (or set OPENAI_API_KEY in .env).');
            }

            if ($provider === 'openai') {
                $apiKey = '';
                if ($settingsAvailable) {
                    try {
                        $apiKey = (string) Setting::getSecret('ai.openai.api_key', '');
                    } catch (\Throwable $e) {
                        $apiKey = '';
                    }
                }

                if (trim($apiKey) === '') {
                    $apiKey = (string) (env('OPENAI_API_KEY', '') ?? '');
                }

                if (trim($apiKey) === '') {
                    return new NullLlmClient('OpenAI is selected but no API key is configured. Set it in Admin → AI Agent (or OPENAI_API_KEY in .env).');
                }

                $model = '';
                if ($settingsAvailable) {
                    try {
                        $model = (string) (Setting::get('ai.openai.model', '') ?? '');
                    } catch (\Throwable $e) {
                        $model = '';
                    }
                }
                if (trim($model) === '') {
                    $model = (string) (env('OPENAI_MODEL', 'gpt-4o') ?? 'gpt-4o');
                }

                // Normalize invalid models (e.g., gpt-5.4, gpt-5.2 -> gpt-4o)
                $model = $this->normalizeOpenAiModel($model);

                // read from settings; 0 or negative means "no limit" and will skip timeout
                $timeout = 0;
                if ($settingsAvailable) {
                    try {
                        $timeout = (int) (Setting::get('ai.openai.timeout', 0) ?? 0);
                    } catch (\Throwable $e) {
                        $timeout = 0;
                    }
                }
                if ($timeout <= 0) {
                    // fall back to env; allow env to also be 0
                    $timeout = (int) (env('OPENAI_TIMEOUT', 0) ?? 0);
                }
                // if still zero or negative treat as maximum
                if ($timeout <= 0) {
                    $timeout = 600;
                }
                // clamp into allowed range
                if ($timeout < 5) $timeout = 5;
                if ($timeout > 600) $timeout = 600;

                return new OpenAiResponsesClient(
                    apiKey: $apiKey,
                    model: $model,
                    timeoutSeconds: $timeout,
                );
            }

            if ($provider === 'anthropic') {
                $apiKey = '';
                if ($settingsAvailable) {
                    try {
                        $apiKey = (string) Setting::getSecret('ai.anthropic.api_key', '');
                    } catch (\Throwable $e) {
                        $apiKey = '';
                    }
                }

                if (trim($apiKey) === '') {
                    $apiKey = (string) (env('ANTHROPIC_API_KEY', '') ?? '');
                }

                if (trim($apiKey) === '') {
                    return new NullLlmClient('Anthropic is selected but no API key is configured. Set it in Admin → AI Agent (or ANTHROPIC_API_KEY in .env).');
                }

                $model = '';
                if ($settingsAvailable) {
                    try {
                        $model = (string) (Setting::get('ai.anthropic.model', '') ?? '');
                    } catch (\Throwable $e) {
                        $model = '';
                    }
                }
                if (trim($model) === '') {
                    $model = (string) (env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022') ?? 'claude-3-5-sonnet-20241022');
                }

                $timeout = 0;
                if ($settingsAvailable) {
                    try {
                        $timeout = (int) (Setting::get('ai.anthropic.timeout', 0) ?? 0);
                    } catch (\Throwable $e) {
                        $timeout = 0;
                    }
                }
                if ($timeout <= 0) {
                    $timeout = (int) (env('ANTHROPIC_TIMEOUT', 0) ?? 0);
                }
                if ($timeout <= 0) {
                    $timeout = 600;
                }
                if ($timeout < 5) $timeout = 5;
                if ($timeout > 600) $timeout = 600;

                return new AnthropicClient(
                    apiKey: $apiKey,
                    model: $model,
                    timeoutSeconds: $timeout,
                );
            }

            if ($provider === 'gemini') {
                $apiKey = '';
                if ($settingsAvailable) {
                    try {
                        $apiKey = (string) Setting::getSecret('ai.gemini.api_key', '');
                    } catch (\Throwable $e) {
                        $apiKey = '';
                    }
                }

                if (trim($apiKey) === '') {
                    $apiKey = (string) (env('GEMINI_API_KEY', '') ?? '');
                }

                if (trim($apiKey) === '') {
                    return new NullLlmClient('Gemini is selected but no API key is configured. Set it in Admin → AI Agent (or GEMINI_API_KEY in .env).');
                }

                $model = '';
                if ($settingsAvailable) {
                    try {
                        $model = (string) (Setting::get('ai.gemini.model', '') ?? '');
                    } catch (\Throwable $e) {
                        $model = '';
                    }
                }
                if (trim($model) === '') {
                    $model = (string) (env('GEMINI_MODEL', 'gemini-2.5-flash') ?? 'gemini-2.5-flash');
                }

                // zero or negative means "no limit" (don't set client timeout)
                $timeout = 0;
                if ($settingsAvailable) {
                    try {
                        $timeout = (int) (Setting::get('ai.gemini.timeout', 0) ?? 0);
                    } catch (\Throwable $e) {
                        $timeout = 0;
                    }
                }
                if ($timeout <= 0) {
                    $timeout = (int) (env('GEMINI_TIMEOUT', 0) ?? 0);
                }
                if ($timeout <= 0) {
                    $timeout = 600;
                }
                if ($timeout < 5) $timeout = 5;
                if ($timeout > 600) $timeout = 600;

                return new GeminiGenerateContentClient(
                    apiKey: $apiKey,
                    model: $model,
                    timeoutSeconds: $timeout,
                );
            }

            return new NullLlmClient('Unknown AI provider selected. Please choose a valid provider in Admin → AI Agent.');
        });

        // Vision binding (for screenshot-based redesign)
        // - Gemini supports vision via the same API key/model.
        // - OpenAI vision can be wired later if needed.
        $this->app->bind(VisionClientInterface::class, function () {
            $settingsAvailable = false;
            try {
                $settingsAvailable = Schema::hasTable('settings');
            } catch (\Throwable $e) {
                $settingsAvailable = false;
            }

            $provider = '';
            if ($settingsAvailable) {
                try {
                    $provider = (string) (Setting::get('ai.provider', '') ?? '');
                } catch (\Throwable $e) {
                    $provider = '';
                }
            }
            $provider = strtolower(trim($provider));
            if ($provider === '') {
                $provider = strtolower(trim((string) (env('AI_PROVIDER', '') ?? '')));
            }

            if ($provider !== 'gemini') {
                return new NullVisionClient('Vision redesign requires Gemini for now. Switch AI provider to Gemini in Admin → AI Agent.');
            }

            $apiKey = '';
            if ($settingsAvailable) {
                try {
                    $apiKey = (string) Setting::getSecret('ai.gemini.api_key', '');
                } catch (\Throwable $e) {
                    $apiKey = '';
                }
            }
            if (trim($apiKey) === '') {
                $apiKey = (string) (env('GEMINI_API_KEY', '') ?? '');
            }
            if (trim($apiKey) === '') {
                return new NullVisionClient('Gemini vision is selected but no API key is configured. Set it in Admin → AI Agent (or GEMINI_API_KEY in .env).');
            }

            $model = '';
            if ($settingsAvailable) {
                try {
                    $model = (string) (Setting::get('ai.gemini.model', '') ?? '');
                } catch (\Throwable $e) {
                    $model = '';
                }
            }
            if (trim($model) === '') {
                $model = (string) (env('GEMINI_MODEL', 'gemini-2.5-flash') ?? 'gemini-2.5-flash');
            }

            // zero or negative = no limit
            $timeout = 0;
            if ($settingsAvailable) {
                try {
                    $timeout = (int) (Setting::get('ai.gemini.timeout', 0) ?? 0);
                } catch (\Throwable $e) {
                    $timeout = 0;
                }
            }
            if ($timeout <= 0) {
                $timeout = (int) (env('GEMINI_TIMEOUT', 0) ?? 0);
            }
            if ($timeout <= 0) {
                $timeout = 600;
            }
            if ($timeout < 5) $timeout = 5;
            if ($timeout > 600) $timeout = 600;

            return new GeminiVisionClient(
                apiKey: $apiKey,
                model: $model,
                timeoutSeconds: $timeout,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user) => (bool) $user->is_admin);
    }

    /**
     * Normalize invalid/outdated OpenAI model names to valid ones.
     */
    private function normalizeOpenAiModel(string $model): string
    {
        $model = trim($model);
        if ($model === '') {
            return 'gpt-4o';
        }

        // Valid OpenAI models (as of March 2026)
        $validModels = [
            'gpt-4o',
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo',
        ];

        if (in_array($model, $validModels, true)) {
            return $model;
        }

        // Any fake/future models (gpt-5.x, gpt-6.x, etc.) default to gpt-4o
        return 'gpt-4o';
    }
}
