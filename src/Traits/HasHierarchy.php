<?php

namespace Aliziodev\LaravelTerms\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait HasHierarchy
{
    /**
     * Boot the HasHierarchy trait
     */
    public static function bootHasHierarchy()
    {
        static::creating(function ($model) {
            $model->validateDepth();
        });

        static::updating(function ($model) {
            if ($model->isDirty('parent_id')) {
                $model->validateDepth();
            }
        });
    }

    /**
     * Validate term depth based on configuration
     * @throws InvalidArgumentException
     */
    public function validateDepth(): bool
    {
        if (!$this->parent_id) {
            return true;
        }

        $maxDepth = $this->getMaxDepth();

        if ($maxDepth <= 0) {
            return true;
        }

        $depth = 1;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            if ($depth > $maxDepth) {
                throw new InvalidArgumentException("Maximum depth exceeded for term type {$this->type}");
            }
            $parent = $parent->parent;
        }

        return true;
    }

    /**
     * Get the path attribute (breadcrumb style)
     */
    public function getPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->join(' > ');
    }

    /**
     * Get the depth attribute
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Get next sibling attribute
     */
    public function getNextSiblingAttribute(): ?self
    {
        return static::where('parent_id', $this->parent_id)
            ->where('order', '>', $this->order)
            ->orderBy('order')
            ->first();
    }

    /**
     * Get previous sibling attribute
     */
    public function getPreviousSiblingAttribute(): ?self
    {
        return static::where('parent_id', $this->parent_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    /**
     * Get siblings relation
     */
    public function siblings(): Builder
    {
        return static::where('parent_id', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->orderBy(config('terms.ordering.column', 'order'));
    }

    /**
     * Get ancestors attribute
     */
    public function getAncestorsAttribute(): Collection
    {
        return $this->ancestors()->get();
    }

    /**
     * Get descendants attribute
     */
    public function getDescendantsAttribute(): Collection
    {
        return $this->descendants()->get();
    }
} 