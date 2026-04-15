<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aliziodev\LaravelTerms\Models\Term findOrCreate(string $name, string $type, array $attributes = [])
 * @method static \Illuminate\Support\Collection findOrCreateMany(array $names, string $type)
 * @method static void attach(Model $model, array $names, string $type, ?string $context = null)
 * @method static void sync(Model $model, array $names, string $type, ?string $context = null)
 * @method static void detach(Model $model, array $names = [], \Aliziodev\LaravelTerms\Enums\TermType|string|null $type = null, ?string $context = null)
 */
class Terms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'terms';
    }
}
