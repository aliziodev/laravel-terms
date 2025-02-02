<?php

namespace Aliziodev\LaravelTerms\Exceptions;

class TermCacheException extends TermException
{
    protected $key;

    public function __construct(string $message, $key = null)
    {
        parent::__construct($message);
        $this->key = $key;
    }

    public function getKey()
    {
        return $this->key;
    }
} 