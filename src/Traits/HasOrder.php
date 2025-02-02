<?php

namespace Aliziodev\LaravelTerms\Traits;

trait HasOrder
{
    public static function bootHasOrder()
    {
        static::creating(function ($model) {
            if (is_null($model->order)) {
                $model->order = static::where('parent_id', $model->parent_id)->max('order') + 1;
            }
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
} 