<?php

namespace Aliziodev\LaravelTerms\Traits;

use Illuminate\Support\Str;

trait Slug
{
    /**
     * Boot the Slug trait
     */
    protected static function bootSlug()
    {
        static::saving(function ($model) {
            $model->generateSlug();
        });
    }

    /**
     * Generate slug for the model
     */
    protected function generateSlug(): void
    {
        // Skip if slug does not need to be updated
        if (!$this->shouldGenerateSlug()) {
            return;
        }

        $source = empty($this->slug) ? $this->name : $this->slug;
        $this->slug = $this->generateUniqueSlug($source);
    }

    /**
     * Check if slug should be generated
     */
    protected function shouldGenerateSlug(): bool
    {
        // Generate if:
        // 1. Model is new (not in database)
        // 2. Slug is empty
        // 3. Name or slug has changed
        return !$this->exists
            || empty($this->slug)
            || $this->isDirty('name')
            || $this->isDirty('slug');
    }

    /**
     * Check if slug exists
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        // Exclude current record if exists
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        // Check both active and soft deleted records
        $query->withTrashed()
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                    ->orWhereNotNull('deleted_at');
            });

        // Check if slug exists with same type
        if ($this->type) {
            $query->where('type', $this->type);
        }

        return $query->exists();
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $value): string
    {
        $separator = config('terms.settings.slug_separator', '-');
        $slug = Str::slug($value, $separator);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . $separator . $counter++;
        }

        return $slug;
    }
}
