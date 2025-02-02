<?php

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'terms:install';

    protected $description = 'Install the Laravel Terms package';

    public function handle()
    {
        $this->info('Installing Laravel Terms...');

        $this->publishConfig();
        $this->publishMigrations();

        $this->info('Laravel Terms installed successfully.');
        $this->info('Please run "php artisan migrate" to create the required tables.');
    }

    protected function publishConfig()
    {
        $this->info('Publishing configuration...');

        if (File::exists(config_path('terms.php'))) {
            if (!$this->confirm('Config file already exists. Do you want to overwrite it?')) {
                return;
            }
        }

        $this->info('Publishing configuration...');
        
        $this->callSilent('vendor:publish', [
            '--provider' => 'Aliziodev\LaravelTerms\Providers\TermServiceProvider',
            '--tag' => 'terms-config'
        ]);

        $this->info('Configuration published successfully.');

    }

    protected function publishMigrations()
    {
        $this->info('Publishing migrations...');

        $this->call('vendor:publish', [
            '--provider' => 'Aliziodev\LaravelTerms\Providers\TermServiceProvider',
            '--tag' => 'migrations'
        ]);

        $this->info('Creating migrations...');

        $timestamp = date('Y_m_d_His');
        $stubPath = __DIR__ . '/../../../database/stubs/create_terms_tables.stub';
        $targetPath = database_path("migrations/{$timestamp}_create_terms_tables.php");

        if (File::exists($stubPath)) {
            File::copy($stubPath, $targetPath);
            $this->info('Migration created successfully.');
        } else {
            $this->error('Migration stub not found.');
        }
    }
}
