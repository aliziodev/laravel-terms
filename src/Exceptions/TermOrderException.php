<?php

namespace Aliziodev\LaravelTerms\Exceptions;

class TermOrderException extends TermException
{
    protected $order;

    public function __construct(string $message, $order = null)
    {
        parent::__construct($message);
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }
} 