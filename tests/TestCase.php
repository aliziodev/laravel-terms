<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Tests;

use Aliziodev\LaravelTerms\TermsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading(false);

        // Remove any migration files published by a previous test so they don't
        // run twice alongside the package's own loadMigrationsFrom() path.
        foreach (glob(database_path('migrations/*_create_terms_tables.php')) ?: [] as $path) {
            @unlink($path);
        }

        $this->artisan('migrate', ['--database' => 'testing'])->run();

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            TermsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('terms.table_names', [
            'terms' => 'terms',
            'termables' => 'termables',
        ]);

        $app['config']->set('terms.morph_type', 'numeric');
    }
}
