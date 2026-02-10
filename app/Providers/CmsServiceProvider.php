<?php

namespace App\Providers;

use App\Support\Cms;
use App\Support\ModuleManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('cms.php'), 'cms');

        $this->app->singleton(ModuleManager::class, function () {
            return new ModuleManager(
                modulesPath: config('cms.modules_path')
            );
        });

        $this->app->singleton(Cms::class, function ($app) {
            return new Cms($app->make(ModuleManager::class));
        });
    }

    public function boot(): void
    {
        // Blade directive: @cmsContent($page->body, $page)
        Blade::directive('cmsContent', function ($expression) {
            return "<?php echo app(\"" . Cms::class . "\")->renderContent($expression); ?>";
        });

        View::composer('*', function ($view) {
            $view->with('cmsTheme', config('cms.theme'));
        });

        // Module discovery + provider registration (safe when DB isn't migrated yet)
        $this->app->make(ModuleManager::class)->registerEnabledProviders($this->app);
    }
}
