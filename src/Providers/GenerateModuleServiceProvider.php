<?php

namespace DianoDev\Providers;

use Illuminate\Support\ServiceProvider;

class GenerateModuleServiceProvider extends ServiceProvider
{
    /**
     * Registra os comandos.
     */
    public function register()
    {
        $this->commands([
            \DianoDev\Console\Commands\GenerateModule::class,
        ]);
    }
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../resources/js/components' => resource_path('./js/components/laravel-vue-crud'),
        ], 'js-components');
        $this->publishes([
            __DIR__ . '/../resources/js/stores' => resource_path('./js/components/laravel-vue-crud'),
        ], 'js-filters');
    }
}
