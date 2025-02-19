<?php

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'terms:install 
        {--force : Overwrite existing files}
        {--skip-controller : Skip publishing the controller}
        {--skip-requests : Skip publishing the request classes}';

    protected $description = 'Install the Laravel Terms package';

    public function handle(): void
    {
        $this->info('Installing Laravel Terms...');

        $this->installConfig();
        $this->installMigrations();
        $this->installController();
        $this->installRequests();
        
        $this->info('Installation completed!');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Review the configuration in config/terms.php');
        $this->info('2. Run "php artisan migrate" to create the database tables');
    }

    protected function installConfig(): void
    {
        $configPath = config_path('terms.php');
        
        if (File::exists($configPath) && !$this->option('force')) {
            if (!$this->confirm('Config file already exists. Do you want to overwrite it?')) {
                $this->info('Skipping config file...');
                return;
            }
        }

        $this->publishTag('terms-config');
        $this->info('✓ Configuration file installed successfully');
    }

    protected function installMigrations(): void
    {
        $this->publishTag('terms-migrations');
        $this->info('✓ Migration files installed successfully');
    }

    protected function installController(): void
    {
        if ($this->option('skip-controller')) {
            $this->info('Skipping controller installation...');
            return;
        }

        $controllerPath = app_path('Http/Controllers/TermController.php');
        
        if (File::exists($controllerPath) && !$this->option('force')) {
            if (!$this->confirm('Controller already exists. Do you want to overwrite it?')) {
                $this->info('Skipping controller...');
                return;
            }
        }

        $this->publishTag('terms-controller');
        $this->info('✓ Controller installed successfully');
    }

    protected function installRequests(): void
    {
        if ($this->option('skip-requests')) {
            $this->info('Skipping request classes installation...');
            return;
        }

        $requestsPath = app_path('Http/Requests/Term');
        
        if (File::exists($requestsPath) && !$this->option('force')) {
            if (!$this->confirm('Request classes already exist. Do you want to overwrite them?')) {
                $this->info('Skipping request classes...');
                return;
            }
        }

        $this->publishTag('terms-requests');
        $this->info('✓ Request classes installed successfully');
    }

    protected function publishTag(string $tag): void
    {
        $this->callSilent('vendor:publish', [
            '--provider' => 'Aliziodev\LaravelTerms\Providers\TermServiceProvider',
            '--tag' => $tag,
            '--force' => $this->option('force'),
        ]);
    }
}
