<?php

namespace Aliziodev\LaravelTerms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Aliziodev\LaravelTerms\Traits\{
    HasMetaAttributes,
    HasHierarchy,
    HasSlug,
    HasOrder
};

class Term extends Model
{
    use SoftDeletes,
        HasMetaAttributes,
        HasHierarchy,
        HasSlug,
        HasOrder;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'parent_id',
        'order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'order' => 'integer',
    ];

    protected $appends = [
        'path',
        'depth',
        'is_leaf',
        'is_root'
    ];

    /**
     * Get the table name from config
     */
    public function getTable()
    {
        return config('terms.table_names.terms', parent::getTable());
    }

    /**
     * Get the type configuration
     */
    public function getTypeConfig(): ?array
    {
        return config("terms.types.{$this->type}");
    }

    /**
     * Check if term type allows hierarchy
     */
    public function isHierarchical(): bool
    {
        return $this->getTypeConfig()['hierarchical'] ?? false;
    }

    /**
     * Get maximum allowed depth for this term type
     */
    public function getMaxDepth(): int
    {
        return $this->getTypeConfig()['max_depth'] ?? config('terms.settings.max_depth', 5);
    }

    /**
     * Query Scopes
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithMeta($query, string $key, $value = null): Builder
    {
        if (is_null($value)) {
            return $query->whereNotNull("meta->{$key}");
        }
        return $query->where("meta->{$key}", $value);
    }

    public function scopeOrdered($query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('order', $direction);
    }

    public function scopeHierarchical($query): Builder
    {
        return $query->whereIn('type', $this->getHierarchicalTypes());
    }

    public function scopeRoots($query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLeaves($query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    public function scopeSearch($query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    public function scopeWithType($query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithParent($query, ?int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Relationship Methods
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')
            ->orderBy(config('terms.ordering.column', 'order'));
    }

    public function models(): MorphToMany
    {
        return $this->morphedByMany(
            config('terms.model', Term::class),
            'termable',
            config('terms.table_names.termables')
        )->withTimestamps();
    }

    /**
     * Hierarchy Methods
     */
    public function ancestors(): Builder
    {
        return static::where(function ($query) {
            $parent = $this->parent;
            $ancestorIds = collect();

            while ($parent) {
                $ancestorIds->push($parent->id);
                $parent = $parent->parent;
            }

            if ($ancestorIds->isNotEmpty()) {
                $query->whereIn('id', $ancestorIds);
            }
        })->ordered();
    }

    public function descendants(): Builder
    {
        return static::where(function ($query) {
            $descendantIds = $this->getAllDescendantIds();

            if ($descendantIds->isNotEmpty()) {
                $query->whereIn('id', $descendantIds);
            }
        })->ordered();
    }


    /**
     * Attribute Methods
     */
    public function getIsLeafAttribute(): bool
    {
        return !$this->children()->exists();
    }

    public function getIsRootAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Helper Methods
     */
    public function isDescendantOf(self $term): bool
    {
        return $this->ancestors()->where('id', $term->id)->exists();
    }

    public function isAncestorOf(self $term): bool
    {
        return $term->isDescendantOf($this);
    }

    public function isSiblingOf(self $term): bool
    {
        return $this->parent_id === $term->parent_id && $this->id !== $term->id;
    }

    protected function getAllDescendantIds(): Collection
    {
        $descendantIds = collect();
        $this->children->each(function ($child) use (&$descendantIds) {
            $descendantIds->push($child->id);
            $descendantIds = $descendantIds->merge($child->getAllDescendantIds());
        });
        return $descendantIds;
    }

    public function toHierarchy(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'children' => $this->children->map->toHierarchy()
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($term) {
            // Validate term type
            $validTypes = array_keys(config('terms.types', []));
            if (!empty($validTypes) && !in_array($term->type, $validTypes)) {
                throw new InvalidArgumentException("Invalid term type: {$term->type}");
            }

            // Auto-generate slug
            if (empty($term->slug)) {
                $term->slug = $term->generateUniqueSlug($term->name, config('terms.settings.slug_separator', '-'));
            } else {
                $term->slug = $term->generateUniqueSlug($term->slug, config('terms.settings.slug_separator', '-'));
            }
            
            // Auto-order if enabled
            if (config('terms.ordering.auto_order', true) && is_null($term->order)) {
                $term->order = static::where('parent_id', $term->parent_id)
                    ->where('type', $term->type)
                    ->max(config('terms.ordering.column', 'order')) + 1;
            }

            // Validate meta keys
            if (!empty($term->meta)) {
                static::validateMetaKeys($term->meta);
            }

            // Set default meta
            if (config('terms.meta.enabled', true) && empty($term->meta)) {
                $term->meta = config('terms.meta.defaults', []);
            }

            // Check circular reference
            $term->preventCircularReference();
        });

        static::updating(function ($term) {
            // Validate term type on update
            $validTypes = array_keys(config('terms.types', []));
            if (!empty($validTypes) && !in_array($term->type, $validTypes)) {
                throw new InvalidArgumentException("Invalid term type: {$term->type}");
            }

            // Validate meta keys if meta is being updated
            if ($term->isDirty('meta') && !empty($term->meta)) {
                static::validateMetaKeys($term->meta);
            }

            // Check circular reference if parent_id is changed
            if ($term->isDirty('parent_id')) {
                $term->preventCircularReference();
            }
        });

        static::deleting(function ($term) {
            if (config('terms.settings.cascade_delete', false)) {
                $term->children()->delete();
            }
        });
    }

    /**
     * Validate meta keys against reserved keys
     *
     * @param array $meta
     * @throws \Exception
     */
    protected static function validateMetaKeys(array $meta)
    {
        $reservedKeys = config('terms.meta.reserved_keys', [
            'id',
            'name',
            'slug',
            'type',
            'description',
            'parent_id',
            'order',
            'created_at',
            'updated_at',
            'deleted_at'
        ]);

        foreach (array_keys($meta) as $key) {
            if (in_array($key, $reservedKeys)) {
                throw new \Exception("Meta key '{$key}' is reserved");
            }
        }
    }

    /**
     * Get hierarchical types from config
     */
    protected function getHierarchicalTypes(): array
    {
        return collect(config('terms.types', []))
            ->filter(fn($config) => $config['hierarchical'] ?? false)
            ->keys()
            ->all();
    }

    protected function preventCircularReference()
    {
        if ($this->parent_id) {
            $parent = static::find($this->parent_id);
            while ($parent) {
                if ($parent->id === $this->id) {
                    throw new \InvalidArgumentException('Circular reference detected');
                }
                $parent = $parent->parent;
            }
        }
    }
}
