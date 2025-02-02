<?php

namespace Aliziodev\LaravelTerms\Console\Commands;

use Illuminate\Console\Command;
use Aliziodev\LaravelTerms\Models\Term;

class SyncTermsOrderCommand extends Command
{
    protected $signature = 'terms:sync-order';
    protected $description = 'Synchronize terms order';

    public function handle()
    {
        $types = array_keys(config('terms.types', []));
        
        foreach ($types as $type) {
            $this->info("Syncing order for type: {$type}");
            
            $terms = Term::where('type', $type)
                ->orderBy(config('terms.ordering.column', 'order'))
                ->get();

            $position = config('terms.ordering.start_position', 1);
            
            foreach ($terms as $term) {
                $term->update([
                    config('terms.ordering.column', 'order') => $position++
                ]);
            }
        }

        $this->info('Terms order synchronized successfully.');
    }
} 