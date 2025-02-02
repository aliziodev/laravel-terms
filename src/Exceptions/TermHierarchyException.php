<?php

namespace Aliziodev\LaravelTerms\Exceptions;

class TermHierarchyException extends TermException
{
    protected $parent;
    protected $child;

    public function __construct(string $message, $child = null, $parent = null)
    {
        parent::__construct($message);
        $this->child = $child;
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChild()
    {
        return $this->child;
    }
} 