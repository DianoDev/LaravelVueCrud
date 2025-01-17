<?php

namespace SeuNamespace;

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
}
