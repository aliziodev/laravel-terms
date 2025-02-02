<?php

namespace Aliziodev\LaravelTerms\Tests;

use Illuminate\Database\Eloquent\Model;
use Aliziodev\LaravelTerms\Traits\HasTerms;

class ProductModel extends Model
{
    use HasTerms;

    protected $fillable = ['name'];
    
    protected $table = 'products';
} 