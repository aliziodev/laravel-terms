<?php

namespace Aliziodev\LaravelTerms\Providers;

use Illuminate\Support\ServiceProvider;
use Aliziodev\LaravelTerms\TermManager;
use Aliziodev\LaravelTerms\Contracts\TermInterface;
use Aliziodev\LaravelTerms\Services\TermService;
use Aliziodev\LaravelTerms\Console\Commands\{
    ClearTermsCacheCommand,
    InstallCommand,
    SyncTermsOrderCommand
};

class TermServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/terms.php',
            'terms'
        );

        // Register main service
        $this->app->singleton('term', function ($app) {
            return new TermManager($app);
        });

        // Register interface binding
        $this->app->bind(TermInterface::class, function ($app) {
            return $app->make('term')->driver();
        });

        // Register default implementation
        $this->app->bind(TermService::class, function ($app) {
            return $app->make(TermInterface::class);
        });

        // Register facade accessor
        $this->app->bind('Term', function ($app) {
            return $app->make(TermInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publish configurations
        $this->publishes([
            __DIR__ . '/../../config/terms.php' => config_path('terms.php'),
        ], 'terms-config');

        // Publish migrations
        if (!class_exists('CreateTermsTables')) {
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__ . '/../../database/stubs/create_terms_tables.stub'
                => database_path("migrations/{$timestamp}_create_terms_tables.php"),
            ], 'terms-migrations');
        }

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ClearTermsCacheCommand::class,
                SyncTermsOrderCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'term',
            TermInterface::class,
            TermService::class,
        ];
    }
}
