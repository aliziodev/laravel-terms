<?php

namespace Aliziodev\LaravelTerms\Exceptions;

class TermNotFoundException extends TermException
{
    protected $identifier;

    public function __construct(string $message, $identifier = null)
    {
        parent::__construct($message);
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
} 