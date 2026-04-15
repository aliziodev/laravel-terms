<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'terms:install {--force : Overwrite existing files without confirmation}';

    protected $description = 'Install Laravel Terms configuration and migration files.';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->publishConfig();
        $this->publishMigration();

        if ($this->confirm('Do you want to run migrations now?', false)) {
            $this->call('migrate');
            $this->info('Migrations completed.');
        } else {
            $this->info('You can run migrations later with php artisan migrate.');
        }

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $source = __DIR__.'/../../../config/terms.php';
        $destination = config_path('terms.php');

        if ($this->files->exists($destination) && ! $this->shouldOverwrite('config file')) {
            $this->warn('Skipped config file.');

            return;
        }

        $this->files->copy($source, $destination);
        $this->info('Config file published to '.$destination.'.');
    }

    protected function publishMigration(): void
    {
        $source = __DIR__.'/../../../database/migrations/2026_04_16_000000_create_terms_tables.php';
        $destination = $this->existingMigrationPath() ?? database_path('migrations/'.now()->format('Y_m_d_His').'_create_terms_tables.php');

        if ($this->files->exists($destination) && ! $this->shouldOverwrite('migration file')) {
            $this->warn('Skipped migration file.');

            return;
        }

        $this->files->ensureDirectoryExists(dirname($destination));
        $this->files->copy($source, $destination);
        $this->info('Migration file published to '.basename($destination).'.');
    }

    protected function shouldOverwrite(string $label): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm('The '.$label.' already exists. Overwrite it?', false);
    }

    protected function existingMigrationPath(): ?string
    {
        $paths = glob(database_path('migrations/*_create_terms_tables.php'));

        return $paths[0] ?? null;
    }
}
