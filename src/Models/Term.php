<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Term extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Term $term): void {
            if (blank($term->slug) && config('terms.slugs.generate', true)) {
                $slug = str($term->name)->slug()->toString();

                if (blank($slug)) {
                    throw new \InvalidArgumentException(
                        "Cannot generate a slug from term name [{$term->name}]. Provide an explicit slug."
                    );
                }

                $term->slug = $slug;
            }
        });

        static::updating(function (Term $term): void {
            if (
                $term->isDirty('name')
                && blank($term->slug)
                && config('terms.slugs.generate', true)
                && config('terms.slugs.regenerate_on_update', false)
            ) {
                $slug = str($term->name)->slug()->toString();

                if (blank($slug)) {
                    throw new \InvalidArgumentException(
                        "Cannot generate a slug from term name [{$term->name}]. Provide an explicit slug."
                    );
                }

                $term->slug = $slug;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Order terms by their sort_order column.
     *
     * @example Term::query()->type('tag')->ordered()->get()
     * @example Term::query()->ordered('desc')->get()
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort_order', $direction);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function termables(string $related): MorphToMany
    {
        return $this->morphedByMany(
            $related,
            'termable',
            config('terms.table_names.termables', 'termables'),
            'term_id',
            'termable_id'
        )->withPivot('context')->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Overrides
    // -------------------------------------------------------------------------

    public function getTable(): string
    {
        return config('terms.table_names.terms', 'terms');
    }
}
