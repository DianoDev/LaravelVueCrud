<?php

namespace DianoDev\app\Providers;

use Illuminate\Support\ServiceProvider;

class GenerateModuleServiceProvider extends ServiceProvider
{
    /**
     * Registra os comandos.
     */
    public function register()
    {
        $this->commands([
            \DianoDev\app\Console\Commands\GenerateModule::class,
        ]);
    }
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../resources/js/components' => resource_path('js/components/laravel-vue-crud'),
        ], 'laravel-vue-crud-components');

        $this->publishes([
            __DIR__ . '/../../resources/sass' => resource_path('sass'),
        ], 'laravel-sass');

        $this->publishes([
            __DIR__ . '/../../app/View' => app_path('View'),
        ], 'laravel-view');

        $this->publishes([
            __DIR__ . '/../../resources/views/components' => resource_path('views/components'),
        ], 'laravel-blade');

        $this->publishes([
            __DIR__ . '/../../resources/js/components' => resource_path('js/components/laravel-vue-crud'),
            __DIR__ . '/../../resources/views/components' => resource_path('views'),
            __DIR__ . '/../../app/View' => app_path('View'),
            __DIR__ . '/../../resources/sass' => resource_path('sass'),
        ], 'laravel-vue-crud');
    }
}
