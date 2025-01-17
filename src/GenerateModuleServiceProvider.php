<?php

namespace dianodev;

use Illuminate\Support\ServiceProvider;

class GenerateModuleServiceProvider extends ServiceProvider
{
    /**
     * Registra os comandos.
     */
    public function register()
    {
        $this->commands([
            \dianodev\Console\Commands\GenerateModule::class,
        ]);
    }
}
