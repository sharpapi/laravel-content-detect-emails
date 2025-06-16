<?php

declare(strict_types=1);

namespace SharpAPI\ContentDetectEmails;

use Illuminate\Support\ServiceProvider;

/**
 * @api
 */
class ContentDetectEmailsProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sharpapi-content-detect-emails.php' => config_path('sharpapi-content-detect-emails.php'),
            ], 'sharpapi-content-detect-emails');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package configuration with the app configuration.
        $this->mergeConfigFrom(
            __DIR__.'/../config/sharpapi-content-detect-emails.php', 'sharpapi-content-detect-emails'
        );
    }
}