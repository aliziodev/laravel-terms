<?php

namespace Aliziodev\LaravelTerms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aliziodev\LaravelTerms\Models\Term create(array $attributes)
 * @method static \Aliziodev\LaravelTerms\Models\Term update(\Aliziodev\LaravelTerms\Models\Term $term, array $attributes)
 * @method static bool delete(\Aliziodev\LaravelTerms\Models\Term $term)
 * @method static \Illuminate\Support\Collection getByType(string $type)
 * @method static \Aliziodev\LaravelTerms\Models\Term|null getBySlug(string $slug)
 * 
 * @see \Aliziodev\LaravelTerms\TermManager
 */
class Term extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'term';
    }
}