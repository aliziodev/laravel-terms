<?php

namespace Aliziodev\LaravelTerms\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface HasTermsInterface
{
    public function terms(): MorphToMany;
    public function syncTerms($terms, ?string $type = null): self;
    public function attachTerms($terms): self;
    public function detachTerms($terms): self;
} 