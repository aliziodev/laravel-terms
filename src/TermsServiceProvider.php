<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms;

use Aliziodev\LaravelTerms\Console\Commands\InstallCommand;
use Aliziodev\LaravelTerms\Contracts\TermsManagerInterface;
use Illuminate\Support\ServiceProvider;

class TermsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/terms.php', 'terms');

        $this->app->singleton(TermsManagerInterface::class, TermsManager::class);
        $this->app->alias(TermsManagerInterface::class, 'terms');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/terms.php' => config_path('terms.php'),
            ], 'terms-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'terms-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
