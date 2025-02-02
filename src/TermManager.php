<?php

namespace Aliziodev\LaravelTerms;

use Illuminate\Support\Manager;
use Aliziodev\LaravelTerms\Services\TermService;
use Aliziodev\LaravelTerms\Contracts\TermInterface;

class TermManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('terms.default', 'database');
    }

    /**
     * Create database driver instance.
     */
    public function createDatabaseDriver(): TermInterface
    {
        return new TermService();
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        // Check if custom driver method exists
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.ucfirst($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new \InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Get all of the created "drivers".
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }
}
