<?php

namespace Aliziodev\LaravelTerms\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Check if slug exists
     */
    public function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);
        
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $name, string $separator = '-'): string
    {
        $slug = Str::slug($name, $separator);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $originalSlug . $separator . $counter++;
        }

        return $slug;
    }
    
} 