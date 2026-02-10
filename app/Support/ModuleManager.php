<?php

namespace App\Support;

use App\Models\Module;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ModuleManager
{
    public function __construct(
        private readonly string $modulesPath
    ) {}

    /**
     * Discover modules by reading: modules/{module}/module.json
     *
     * module.json example:
     * {
     *   "name": "ContactForms",
     *   "slug": "contact-forms",
     *   "provider": "Modules\\\\ContactForms\\\\ContactFormsServiceProvider",
     *   "enabled": true
     * }
     *
     * @return array<int, array{name:string,slug:string,provider:string,enabled:bool}>
     */
    public function discover(): array
    {
        if (!is_dir($this->modulesPath)) {
            return [];
        }

        $modules = [];
        foreach (File::directories($this->modulesPath) as $dir) {
            $jsonPath = $dir . DIRECTORY_SEPARATOR . 'module.json';
            if (!File::exists($jsonPath)) {
                continue;
            }

            $decoded = json_decode(File::get($jsonPath), true);
            if (!is_array($decoded)) {
                continue;
            }

            $name = (string)($decoded['name'] ?? basename($dir));
            $slug = (string)($decoded['slug'] ?? str($name)->slug()->value());

            $modules[] = [
                'name' => $name,
                'slug' => $slug,
                'provider' => (string)($decoded['provider'] ?? ''),
                'enabled' => (bool)($decoded['enabled'] ?? true),
            ];
        }

        return $modules;
    }

    /**
     * Return enabled modules from DB if available, else fallback to discovered module.json
     *
     * @return array<int, array{name:string,slug:string,provider:string,enabled:bool}>
     */
    public function enabled(): array
    {
        if (Schema::hasTable('modules')) {
            return Module::query()
                ->where('enabled', true)
                ->get(['name', 'slug', 'provider_class as provider', 'enabled'])
                ->map(fn ($m) => [
                    'name' => $m->name,
                    'slug' => $m->slug,
                    'provider' => $m->provider,
                    'enabled' => (bool) $m->enabled,
                ])
                ->values()
                ->all();
        }

        return array_values(array_filter(
            $this->discover(),
            fn ($m) => !empty($m['provider']) && $m['enabled'] === true
        ));
    }

    public function registerEnabledProviders(Application $app): void
    {
        foreach ($this->enabled() as $module) {
            if (!empty($module['provider']) && class_exists($module['provider'])) {
                $app->register($module['provider']);
            }
        }
    }
}
