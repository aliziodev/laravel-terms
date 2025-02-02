<?php

namespace Aliziodev\LaravelTerms\Exceptions;

use Exception;

class TermException extends Exception
{
    protected $term;

    public function __construct(string $message, $term = null)
    {
        parent::__construct($message);
        $this->term = $term;
    }

    public function getTerm()
    {
        return $this->term;
    }
} 