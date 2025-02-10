<?php

namespace Aliziodev\LaravelTerms\Traits;

use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

trait HasTerms
{
    /**
     * Basic Term Relations
     */
    public function terms(): MorphToMany
    {
        return $this->morphToMany(Term::class, 'termable', config('terms.table_names.termables'))
            ->withTimestamps();
    }

    /**
     * Term Type Methods
     */
    public function termsOfType(string $type): MorphToMany
    {
        return $this->terms()->where('type', $type);
    }

    public function getTermsByType(string $type): Collection
    {
        return $this->termsOfType($type)->get();
    }

    public function hasTermOfType(string $type): bool
    {
        return $this->termsOfType($type)->exists();
    }

    /**
     * Term Attachment Methods
     */
    public function attachTerm($term): void
    {
        $termId = $term instanceof Term ? $term->id : $term;
        $this->terms()->attach($termId);
    }

    /**
     * Sync terms by type
     * 
     * @param array|int|string $terms Term IDs atau instances
     * @param string|null $type Type dari term (category, tag, etc)
     */
    public function syncTerms($terms, ?string $type = null): void
    {
        // Convert to array jika single value
        $terms = is_array($terms) ? $terms : [$terms];

        $termIds = collect($terms)
            ->map(fn($term) => $this->getTermId($term))
            ->filter()
            ->all();

        if ($type) {
            $this->terms()
                ->whereIn('terms.id', $termIds)
                ->sync($termIds);
        } else {
            // Sync all terms if no type specified
            $this->terms()->sync($termIds);
        }
    }

    /**
     * Attach multiple terms
     * 
     * @param array|Collection $terms
     */
    public function attachTerms($terms): void
    {
        $termIds = collect($terms)
            ->map(fn($term) => $this->getTermId($term))
            ->filter()
            ->all();

        if (!empty($termIds)) {
            $this->terms()->attach($termIds);
        }
    }

    /**
     * Term Management Methods
     */
    public function detachTerm($term): void
    {
        $termId = $term instanceof Term ? $term->id : $term;
        $this->terms()->detach($termId);
    }

    /**
     * Detach multiple terms
     * 
     * @param array|Collection $terms
     */
    public function detachTerms($terms): void
    {
        $termIds = collect($terms)
            ->map(fn($term) => $this->getTermId($term))
            ->filter()
            ->all();

        if (!empty($termIds)) {
            $this->terms()->detach($termIds);
        }
    }

    /**
     * Check if model has multiple terms
     * 
     * @param array|Collection $terms
     */
    public function hasTerms($terms): bool
    {
        $termIds = collect($terms)
            ->map(fn($term) => $this->getTermId($term))
            ->filter();
        return $this->terms()->whereIn('terms.id', $termIds)->count() === $termIds->count();
    }

    /**
     * Term Meta Methods
     */
    public function setTermMeta($term, array $meta)
    {
        if ($termId = $this->getTermId($term)) {
            Term::where('id', $termId)->update(['meta' => $meta]);
        }
        return $this;
    }

    public function getTermMeta($term)
    {
        if ($termId = $this->getTermId($term)) {
            return Term::where('id', $termId)->value('meta') ?? [];
        }
        return [];
    }

    /**
     * Term Ordering Methods
     */
    public function getOrderedTerms(): Collection
    {
        return $this->terms()->orderBy('terms.order')->get();
    }

    /**
     * Term Validation Methods
     */
    public function hasTerm($term): bool
    {
        return $this->terms()->where('terms.id', $this->getTermId($term))->exists();
    }


    /**
     * Advanced Query Methods
     */
    public function getTermsByMeta(string $key, $value): Collection
    {
        return $this->terms()
            ->wherePivot("meta->{$key}", $value)
            ->get();
    }

    public function getTermsWithAttribute(string $key): Collection
    {
        return $this->terms()
            ->whereNotNull("meta->{$key}")
            ->get();
    }

    public function getTermsHierarchy(): Collection
    {
        return $this->terms()
            ->whereNull('parent_id')
            ->with('children')
            ->get();
    }

    public function getTermsAsTree(): array
    {
        return $this->getTermsHierarchy()
            ->map(fn($term) => $term->toHierarchy())
            ->all();
    }

    /**
     * Batch Operations
     */
    public function syncTermsWithoutDetaching($terms): self
    {
        $syncData = collect($terms)
            ->map(fn($term) => $this->getTermId($term))
            ->filter()
            ->mapWithKeys(fn($id) => [$id => []])
            ->all();

        if (!empty($syncData)) {
            $this->terms()->syncWithoutDetaching($syncData);
        }

        return $this;
    }

    /**
     * Counting Methods
     */
    public function countTerms(?string $type = null): int
    {
        $query = $this->terms();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->count();
    }

    public function getTermsCount(): array
    {
        return $this->terms()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->all();
    }

    /**
     * Sorting Methods
     */
    public function sortTermsByField(string $field, string $direction = 'asc'): Collection
    {
        return $this->terms()
            ->orderBy($field, $direction)
            ->get();
    }

    public function getTermsSortedByMeta(string $key, string $direction = 'asc'): Collection
    {
        return $this->terms()
            ->orderByPivot("meta->{$key}", $direction)
            ->get();
    }

    /**
     * Primary Term Methods
     */
    public function setPrimaryTerm($term, string $type = null)
    {
        if ($termId = $this->getTermId($term)) {
            // Reset existing primary terms
            $this->terms()
                ->when($type, fn($q) => $q->where('type', $type))
                ->update(['terms.meta->is_primary' => false]);

            // Set new primary term
            $this->terms()
                ->where('terms.id', $termId)
                ->update(['terms.meta->is_primary' => true]);
        }
        return $this;
    }

    public function getPrimaryTerm(?string $type = null): ?Term
    {
        return $this->terms()
            ->where('terms.meta->is_primary', true)
            ->when($type, fn($q) => $q->where('type', $type))
            ->first();
    }

    /**
     * Batch Meta Methods
     */
    public function updateTermsMeta(array $termMeta)
    {
        foreach ($termMeta as $termId => $meta) {
            Term::where('id', $termId)->update(['meta' => $meta]);
        }
        return $this;
    }

    public function syncTermsMeta(array $termMeta, bool $detaching = true)
    {
        if ($detaching) {
            // Reset meta for terms not in the array
            Term::whereNotIn('id', array_keys($termMeta))
                ->update(['meta' => null]);
        }

        return $this->updateTermsMeta($termMeta);
    }

    /**
     * Advanced Query Methods
     */
    public function getTermsWithMetaValue(string $key, $value): Collection
    {
        return $this->terms()
            ->wherePivot("meta->{$key}", $value)
            ->get();
    }

    public function getTermsByMultipleMeta(array $meta): Collection
    {
        $query = $this->terms();

        foreach ($meta as $key => $value) {
            $query->wherePivot("meta->{$key}", $value);
        }

        return $query->get();
    }

    /**
     * Reorder terms
     */
    public function reorderTerms(array $termIds): self
    {
        foreach ($termIds as $order => $termId) {
            Term::where('id', $termId)->update(['order' => $order + 1]);
        }
        return $this;
    }

    /**
     * Move term before another term
     */
    public function moveTermBefore($term, $targetTerm): self
    {
        if (($termId = $this->getTermId($term)) && ($targetId = $this->getTermId($targetTerm))) {
            $targetOrder = Term::where('id', $targetId)->value('order');

            if ($targetOrder) {
                // Increment order of terms after target
                Term::where('order', '>=', $targetOrder)
                    ->increment('order');

                // Set new order for term
                Term::where('id', $termId)
                    ->update(['order' => $targetOrder]);
            }
        }
        return $this;
    }

    /**
     * Move term after another term
     */
    public function moveTermAfter($term, $targetTerm): self
    {
        if (($termId = $this->getTermId($term)) && ($targetId = $this->getTermId($targetTerm))) {
            $targetOrder = Term::where('id', $targetId)->value('order');

            if ($targetOrder) {
                // Increment order of terms after target
                Term::where('order', '>', $targetOrder)
                    ->increment('order');

                // Set new order for term
                Term::where('id', $termId)
                    ->update(['order' => $targetOrder + 1]);
            }
        }
        return $this;
    }

    /**
     * Helper Methods
     */
    protected function getTermId($term)
    {
        if (is_numeric($term)) {
            return Term::find($term)?->id;
        }

        if (is_string($term)) {
            return Term::where('slug', $term)->first()?->id;
        }

        if ($term instanceof Term) {
            return $term->id;
        }

        return null;
    }
}
