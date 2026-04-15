<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Traits;

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTerms
{
    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function terms(): MorphToMany
    {
        return $this->morphToMany(
            config('terms.model', Term::class),
            'termable',
            config('terms.table_names.termables', 'termables'),
            'termable_id',
            'term_id'
        )->withPivot('context')->withTimestamps();
    }

    public function termsOfType(TermType|string $type): MorphToMany
    {
        return $this->terms()->where('type', TermType::toValue($type));
    }

    // -------------------------------------------------------------------------
    // Mutators (chainable)
    // -------------------------------------------------------------------------

    public function syncTerms(array $names, TermType|string $type, ?string $context = null): static
    {
        app('terms')->sync($this, $names, $type, $context);

        return $this;
    }

    public function attachTerms(array $names, TermType|string $type, ?string $context = null): static
    {
        app('terms')->attach($this, $names, $type, $context);

        return $this;
    }

    /**
     * Detach terms from this model.
     *
     * - Pass $names to remove specific terms.
     * - Pass $type to restrict removal to one type.
     * - Pass $context to restrict removal to one pivot context.
     * - Pass nothing to remove all terms.
     */
    public function detachTerms(array $names = [], TermType|string|null $type = null, ?string $context = null): static
    {
        app('terms')->detach($this, $names, $type, $context);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Check whether at least one term of the given type and slug is attached.
     */
    public function hasTerm(TermType|string $type, string $slug): bool
    {
        return $this->terms()
            ->where('type', TermType::toValue($type))
            ->where('slug', $slug)
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    /**
     * Scope: models that have the given term attached.
     *
     * @example Product::whereHasTerm(TermType::Brand, 'nike')->get()
     */
    public function scopeWhereHasTerm(Builder $query, TermType|string $type, string $slug): Builder
    {
        return $query->whereHas('terms', fn (Builder $builder): Builder => $builder
            ->where('type', TermType::toValue($type))
            ->where('slug', $slug)
        );
    }

    /**
     * Scope: models that have ALL (logic='and') or ANY (logic='or') of the given terms.
     *
     * @param  array<string>  $slugs
     * @param  'and'|'or'  $logic
     *
     * @example Product::whereHasTerms(TermType::Tag, ['new', 'sale'])->get()      // AND
     * @example Product::whereHasTerms(TermType::Tag, ['new', 'sale'], 'or')->get() // OR
     */
    public function scopeWhereHasTerms(
        Builder $query,
        TermType|string $type,
        array $slugs,
        string $logic = 'and',
    ): Builder {
        $type = TermType::toValue($type);

        if ($logic === 'or') {
            return $query->whereHas('terms', fn (Builder $builder): Builder => $builder
                ->where('type', $type)
                ->whereIn('slug', $slugs)
            );
        }

        // AND: every slug must be individually present
        foreach ($slugs as $slug) {
            $query->whereHas('terms', fn (Builder $builder): Builder => $builder
                ->where('type', $type)
                ->where('slug', $slug)
            );
        }

        return $query;
    }
}
