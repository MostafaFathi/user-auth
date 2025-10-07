<?php

namespace MostafaFathi\UserAuth;

use Illuminate\Support\ServiceProvider;
use MostafaFathi\UserAuth\Services\AuthService;

class UserAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Get the package base directory + test
        $packageBaseDir = dirname(__DIR__);

        // Publish configuration
        $this->publishes([
            $packageBaseDir . '/config/user-auth.php' => config_path('user-auth.php'),
        ], 'user-auth-config');

        // Publish migrations
        $this->publishes([
            $packageBaseDir . '/database/migrations' => database_path('migrations'),
        ], 'user-auth-migrations');

        // Load routes if the file exists
        $routesPath = $packageBaseDir . '/routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Register console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                // We'll register this later after creating the command
            ]);
        }
    }

    public function register()
    {
        $configPath = dirname(__DIR__) . '/config/user-auth.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'user-auth');
        }

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
    }
}
