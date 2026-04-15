<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;

return [
    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by this package. Useful when your project
    | already has a "terms" or "termables" table.
    |
    */
    'table_names' => [
        'terms' => 'terms',
        'termables' => 'termables',
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph Key Type
    |--------------------------------------------------------------------------
    |
    | Controls the column type for the polymorphic "termable_id" key in the
    | termables pivot table. Must match the primary key type of the models
    | that use the HasTerms trait.
    |
    | Supported: "numeric", "uuid", "ulid"
    |
    */
    'morph_type' => 'numeric',

    /*
    |--------------------------------------------------------------------------
    | Built-in Term Types
    |--------------------------------------------------------------------------
    |
    | The default list of allowed term types. The package also accepts any
    | arbitrary string, so you are not limited to this list at runtime.
    |
    */
    'types' => TermType::values(),

    /*
    |--------------------------------------------------------------------------
    | Term Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used to represent a term. Swap this to your own class
    | if you need to extend or override the default behaviour.
    |
    */
    'model' => Term::class,

    /*
    |--------------------------------------------------------------------------
    | Slug Options
    |--------------------------------------------------------------------------
    |
    | generate            — Auto-generate a slug from the name on create.
    | regenerate_on_update — Re-generate the slug when the name changes and
    |                        the slug field is explicitly cleared.
    |
    */
    'slugs' => [
        'generate' => true,
        'regenerate_on_update' => false,
    ],
];
