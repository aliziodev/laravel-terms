<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Tests\Fixtures;

use Aliziodev\LaravelTerms\Traits\HasTerms;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasTerms;

    protected $guarded = [];
}
