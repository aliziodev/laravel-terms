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
            'order' => 'integer',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Term $term): void {
            if (blank($term->slug) && config('terms.slugs.generate', true)) {
                $term->slug = self::generateSlugFromName($term->name);
            }
        });

        static::updating(function (Term $term): void {
            if (! config('terms.slugs.generate', true)) {
                return;
            }

            // Guard 1: regenerate slug when name changes and regenerate_on_update is enabled.
            // Only runs when the user did not explicitly change the slug themselves.
            if (
                $term->isDirty('name')
                && ! $term->isDirty('slug')
                && config('terms.slugs.regenerate_on_update', false)
            ) {
                $term->slug = self::generateSlugFromName($term->name);

                return;
            }

            // Guard 2: never allow saving a blank slug, regardless of regenerate_on_update.
            // Generates from the current name if the slug was cleared.
            if (blank($term->slug)) {
                $term->slug = self::generateSlugFromName($term->name);
            }
        });
    }

    private static function generateSlugFromName(string $name): string
    {
        $slug = str($name)->slug()->toString();

        if (blank($slug)) {
            throw new \InvalidArgumentException(
                "Cannot generate a slug from term name [{$name}]. Provide an explicit slug."
            );
        }

        return $slug;
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
     * Order terms by their order column.
     *
     * @example Term::query()->type('tag')->ordered()->get()
     * @example Term::query()->ordered('desc')->get()
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('order', $direction);
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
