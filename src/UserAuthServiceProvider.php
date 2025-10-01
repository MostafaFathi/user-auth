<?php

namespace MostafaFathi\UserAuth;

use Illuminate\Support\ServiceProvider;
use YourVendor\UserAuth\Services\AuthService;

class UserAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/user-auth.php' => config_path('user-auth.php'),
        ], 'user-auth-config');

        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'user-auth-migrations');

        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Register console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\MigrateExistingUsers::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/user-auth.php', 'user-auth'
        );

        $this->app->singleton(AuthService::class, function ($app) {
            return new AuthService();
        });
    }
}