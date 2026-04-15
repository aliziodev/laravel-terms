<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(config_path('terms.php'));

    foreach (File::glob(database_path('migrations/*_create_terms_tables.php')) as $path) {
        File::delete($path);
    }
});

it('publishes config and migration files and can run migrations later', function (): void {
    $this->artisan('terms:install')
        ->expectsConfirmation('Do you want to run migrations now?', 'no')
        ->assertExitCode(0);

    expect(File::exists(config_path('terms.php')))->toBeTrue()
        ->and(File::glob(database_path('migrations/*_create_terms_tables.php')))->not->toBeEmpty();
});

it('asks before overwriting existing files', function (): void {
    File::put(config_path('terms.php'), "<?php return ['existing' => true];\n");

    $existingMigration = database_path('migrations/2025_01_01_000000_create_terms_tables.php');
    File::put($existingMigration, "<?php return [];\n");

    $this->artisan('terms:install')
        ->expectsConfirmation('The config file already exists. Overwrite it?', 'yes')
        ->expectsConfirmation('The migration file already exists. Overwrite it?', 'no')
        ->expectsConfirmation('Do you want to run migrations now?', 'no')
        ->assertExitCode(0);

    expect(File::get(config_path('terms.php')))->toContain('table_names')
        ->and(File::get($existingMigration))->toContain('return [];');
});

it('can overwrite existing files with force option', function (): void {
    File::put(config_path('terms.php'), "<?php return ['existing' => true];\n");

    $existingMigration = database_path('migrations/2025_01_01_000000_create_terms_tables.php');
    File::put($existingMigration, "<?php return [];\n");

    $this->artisan('terms:install', ['--force' => true])
        ->expectsConfirmation('Do you want to run migrations now?', 'no')
        ->assertExitCode(0);

    expect(File::get(config_path('terms.php')))->toContain('table_names')
        ->and(File::get($existingMigration))->toContain('timestamps');
});
