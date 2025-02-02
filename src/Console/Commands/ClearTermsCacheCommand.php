<?php

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;
use Aliziodev\LaravelTerms\Facades\Term;

class ClearTermsCacheCommand extends Command
{
    protected $signature = 'terms:clear-cache';
    protected $description = 'Clear the terms cache';

    public function handle()
    {
        Term::clearCache();
        $this->info('Terms cache cleared successfully.');
    }
} 