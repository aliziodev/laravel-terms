<?php

namespace Aliziodev\LaravelTerms\Exceptions;

class TermValidationException extends TermException
{
    protected $errors;

    public function __construct(string $message, array $errors = [], $term = null)
    {
        parent::__construct($message, $term);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
} 