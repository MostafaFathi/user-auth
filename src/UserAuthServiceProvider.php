<?php

namespace MostafaFathi\UserAuth;

use Illuminate\Support\ServiceProvider;
use MostafaFathi\UserAuth\Services\AuthService;

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
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'user-auth');
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
