<?php

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;

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

        $this->publishTag('terms-config');
        $this->info('✓ Configuration file installed successfully');

        $this->publishTag('terms-migrations');
        $this->info('✓ Migration files installed successfully');

        if (!$this->option('skip-controller')) {
            $this->publishTag('terms-controller');
            $this->info('✓ Controller installed successfully');
        }

        if (!$this->option('skip-requests')) {
            $this->publishTag('terms-requests');
            $this->info('✓ Request classes installed successfully');
        }
        
        $this->info('Installation completed!');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Review the configuration in config/terms.php');
        $this->info('2. Run "php artisan migrate" to create the database tables');
    }

    protected function publishTag(string $tag): void
    {
        $this->call('vendor:publish', [
            '--provider' => 'Aliziodev\LaravelTerms\TermServiceProvider',
            '--tag' => $tag,
            '--force' => $this->option('force'),
        ]);
    }
}
