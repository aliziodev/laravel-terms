<?php

namespace Aliziodev\LaravelTerms\Tests;

use Aliziodev\LaravelTerms\Providers\TermServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Facades\Schema;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            TermServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
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

        $app['config']->set('terms.types', [
            'category' => [
                'hierarchical' => true,
                'max_depth' => 3,
                'allow_same_slug' => false,
            ],
            'tag' => [
                'hierarchical' => false,
                'max_depth' => 0,
                'allow_same_slug' => true,
            ],
        ]);

        $app['config']->set('terms.settings', [
            'auto_slug' => true,
            'max_depth' => 5,
            'cascade_delete' => false,
        ]);

        $app['config']->set('terms.ordering', [
            'auto_order' => true,
            'column' => 'order',
        ]);

        $app['config']->set('terms.meta', [
            'enabled' => true,
            'defaults' => [],
            'reserved_keys' => [
                'id',
                'name',
                'slug',
                'type',
                'description',
                'parent_id',
                'order',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
        ]);

        $app['config']->set('terms.cache', [
            'enabled' => true,
            'prefix' => 'terms',
            'ttl' => 3600,
        ]);

        $app['config']->set('terms.events', [
            'created' => true,
            'updated' => true,
            'deleted' => true,
            'restored' => true,
        ]);
    }

    protected function setUpDatabase()
    {
        $migration = include __DIR__ . '/database/migrations/2025_01_01_000000_create_test_tables.php';
        $migration->up();
    }

    protected function createTestModel(): TestModel
    {
        return TestModel::create(['name' => 'Test Model']);
    }

    protected function createProduct(): ProductModel
    {
        return ProductModel::create(['name' => 'Test Product']);
    }
}
