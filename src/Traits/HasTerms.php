<?php

namespace Aliziodev\LaravelTerms\Traits;

use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait HasTerms
{
    /**
     * Cache untuk term instances
     */
    protected array $cachedTerms = [];

    /**
     * Reset cache
     */
    protected function resetTermCache(): void
    {
        $this->cachedTerms = [];
    }

    /**
     * Basic Term Relations
     */
    public function terms(): MorphToMany
    {
        return $this->morphToMany(Term::class, 'termable', config('terms.table_names.termables'))
            ->withTimestamps()
            ->orderBy('order');
    }

    /**
     * Helper Methods - Get Term ID with caching
     */
    protected function getTermId($term)
    {
        if (is_numeric($term)) {
            $cacheKey = "id_{$term}";
            if (!isset($this->cachedTerms[$cacheKey])) {
                $this->cachedTerms[$cacheKey] = Term::find($term)?->id;
            }
            return $this->cachedTerms[$cacheKey];
        }

        if (is_string($term)) {
            $cacheKey = "slug_{$term}";
            if (!isset($this->cachedTerms[$cacheKey])) {
                $this->cachedTerms[$cacheKey] = Term::where('slug', $term)
                    ->orWhere('name', $term)
                    ->first()?->id;
            }
            return $this->cachedTerms[$cacheKey];
        }

        if ($term instanceof Term) {
            return $term->id;
        }

        return null;
    }

    /**
     * Convert terms to IDs
     */
    protected function getTermIds($terms): array
    {
        return collect($terms)
            ->flatten()
            ->map(fn($term) => $this->getTermId($term))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Term Management Methods
     */
    public function syncTerms($terms, ?string $type = null): self
    {
        DB::transaction(function () use ($terms, $type) {
            $termIds = $this->getTermIds(is_array($terms) ? $terms : [$terms]);

            if ($type) {
                // Hanya sync terms dengan type yang sama
                $this->terms()
                    ->where('type', $type)
                    ->sync($termIds);
            } else {
                // Sync semua terms jika tidak ada type
                $this->terms()->sync($termIds);
            }
            
            $this->resetTermCache();
        });

        return $this;
    }

    public function attachTerms($terms): self
    {
        DB::transaction(function () use ($terms) {
            $termIds = $this->getTermIds($terms);

            if (!empty($termIds)) {
                $maxOrder = $this->terms()->max('order') ?? 0;
                
                collect($termIds)->each(function ($termId, $index) use ($maxOrder) {
                    $this->terms()->attach($termId, [
                        'order' => $maxOrder + $index + 1
                    ]);
                });
            }
            
            $this->resetTermCache();
        });

        return $this;
    }

    public function detachTerms($terms): self
    {
        DB::transaction(function () use ($terms) {
            $termIds = $this->getTermIds($terms);

            if (!empty($termIds)) {
                $this->terms()->detach($termIds);
            }
            
            $this->resetTermCache();
        });

        return $this;
    }

    /**
     * Get terms grouped by type
     */
    public function getTermsByGroups(?array $types = null): Collection
    {
        $query = $this->terms();
        
        if ($types) {
            $query->whereIn('type', $types);
        }

        return $query->get()->groupBy('type');
    }

    /**
     * Get terms by specific type
     */
    public function getTermsByType(string $type): Collection
    {
        return $this->terms()->where('type', $type)->get();
    }

    /**
     * Get terms as array grouped by type
     */
    public function getTermsArray(?array $types = null): array
    {
        return $this->getTermsByGroups($types)
            ->map(function ($terms) {
                return $terms->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'meta' => $term->meta
                    ];
                });
            })
            ->all();
    }
}