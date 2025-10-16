<?php

namespace MostafaFathi\UserAuth;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use MostafaFathi\UserAuth\Console\Commands\UpdateControllersNamespace;
use MostafaFathi\UserAuth\Services\SsoAuthService;

class UserAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Get the package base directory + test
        $packageBaseDir = dirname(__DIR__);

        // Publish configuration
        $this->publishes([
            $packageBaseDir . '/config/user-auth.php' => config_path('user-auth.php'),
        ], 'sso-user-auth-config');

        // Publish migrations
        $this->publishes([
            $packageBaseDir . '/database/migrations' => database_path('migrations'),
        ], 'sso-user-auth-migrations');

        $this->publishes([
            $packageBaseDir . '/src/Http/Controllers' => app_path('Http/Controllers/Auth'),
        ], 'sso-user-auth-controllers');
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
        $this->updateControllersNamespaceAfterPublish();

    }

    public function register()
    {
        $configPath = dirname(__DIR__) . '/config/user-auth.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'user-auth');
        }

        $this->app->singleton(SsoAuthService::class, function ($app) {
            return new SsoAuthService();
        });
    }

    /**
     * Update the namespace of published controllers after publishing
     */
    protected function updateControllersNamespaceAfterPublish()
    {
        // This will run after the publish command completes
        $this->app->booted(function () {
            if ($this->app->runningInConsole()) {
                // Check if we just published controllers
                $publishedPath = app_path('Http/Controllers/Auth');

                if (File::exists($publishedPath)) {
                    dd($publishedPath);
                    $this->updateControllersNamespace($publishedPath);
                }
            }
        });
    }

    /**
     * Update namespace in controller files
     */
    protected function updateControllersNamespace(string $controllersPath)
    {
        $files = File::allFiles($controllersPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file);

                // Replace namespace
                $newContent = str_replace(
                    'namespace MostafaFathi\UserAuth\Http\Controllers;',
                    'namespace App\Http\Controllers\Auth;',
                    $content
                );
                dd('test',$newContent);
                File::put($file, $newContent);
            }
        }
    }
}
