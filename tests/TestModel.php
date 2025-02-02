<?php

namespace Aliziodev\LaravelTerms\Tests;

use Illuminate\Database\Eloquent\Model;
use Aliziodev\LaravelTerms\Traits\HasTerms;

class TestModel extends Model
{
    use HasTerms;

    protected $fillable = ['name'];
    protected $table = 'test_models';
} 