<?php

namespace Aliziodev\LaravelTerms;

use Illuminate\Support\ServiceProvider;
use Aliziodev\LaravelTerms\Console\Commands\InstallCommand;
use Aliziodev\LaravelTerms\Models\Term;

class TermServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/terms.php',
            'terms'
        );

        // Register facade accessor
        $this->app->bind('Term', function ($app) {
            return $app->make(Term::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerCommands();
    }

    /**
     * Register publishable resources
     */
    protected function registerPublishables(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Config
        $this->publishes([
            __DIR__ . '/../config/terms.php' => config_path('terms.php'),
        ], 'terms-config');

        // Migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'terms-migrations');

        // Controller
        $this->publishes([
            __DIR__ . '/Http/Controllers/Term/TermController.php' => app_path('Http/Controllers/TermController.php'),
        ], 'terms-controller');

        // Requests
        $this->publishes([
            __DIR__ . '/Http/Requests/Term/StoreTermRequest.php' => app_path('Http/Requests/Term/StoreTermRequest.php'),
            __DIR__ . '/Http/Requests/Term/UpdateTermRequest.php' => app_path('Http/Requests/Term/UpdateTermRequest.php'),
        ], 'terms-requests');
    }

    /**
     * Register commands
     */
    protected function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }
}