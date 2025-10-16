<?php

namespace MostafaFathi\UserAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateControllersNamespace extends Command
{
    protected $signature = 'user-auth:update-controllers';
    protected $description = 'Update the namespace of published controllers';

    public function handle()
    {
        $controllersPath = app_path('Http/Controllers/UserAuth');

        if (!File::exists($controllersPath)) {
            $this->error('No published controllers found. Please publish first.');
            return;
        }

        $files = File::allFiles($controllersPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file);

                // Replace namespace
                $newContent = str_replace(
                    'namespace MostafaFathi\UserAuth\Http\Controllers;',
                    'namespace App\Http\Controllers\UserAuth;',
                    $content
                );

                // Also update any class references if needed
                $newContent = str_replace(
                    'MostafaFathi\UserAuth\Http\Controllers\\',
                    'App\Http\Controllers\UserAuth\\',
                    $newContent
                );

                File::put($file, $newContent);
                $this->info("Updated: " . $file->getFilename());
            }
        }

        $this->info('All controllers namespace updated successfully!');
    }
}