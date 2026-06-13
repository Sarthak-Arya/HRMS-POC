<?php

namespace App\Providers;

use App\Services\Ai\AgentOrchestrator;
use App\Services\Ai\OpenRouterClient;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\EmployeeToolProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            $registry->registerMany(EmployeeToolProvider::tools());

            return $registry;
        });

        $this->app->singleton(OpenRouterClient::class);
        $this->app->singleton(AgentOrchestrator::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
