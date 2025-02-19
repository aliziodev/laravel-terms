<?php

namespace Aliziodev\LaravelTerms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use InvalidArgumentException;
use Aliziodev\LaravelTerms\Traits\{
    Slug,
    TermScope
};

/**
 * @property-read string $path Full path of the term
 * @property-read int $depth Depth level in hierarchy
 * @property-read bool $is_leaf Whether term has no children
 * @property-read bool $is_root Whether term has no parent
 */
class Term extends Model
{
    use SoftDeletes,
        TermScope,
        Slug;

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
    public function getTable(): string
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

    public function models(): MorphToMany
    {
        return $this->morphedByMany(
            config('terms.model', Term::class),
            'termable',
            config('terms.table_names.termables')
        )->withTimestamps();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($term) {
            // Validate term type
            if (!static::isValidType($term->type)) {
                throw new InvalidArgumentException("Invalid term type: {$term->type}");
            }

            // Validate meta keys
            if (!empty($term->meta)) {
                static::validateMetaKeys($term->meta);
            }

            // Check circular reference
            $term->preventCircularReference();
        });

        static::updating(function ($term) {
            // Validate term type on update
            if ($term->isDirty('type') && !static::isValidType($term->type)) {
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

        // Event untuk soft delete
        static::deleting(function ($term) {
            if ($term->isForceDeleting()) {
                return;
            }

            if (config('terms.settings.cascade_delete', false)) {
                $term->children()->delete();
            } else {
                $term->children()->update(['parent_id' => null]);
            }
        });

        // Event untuk force delete
        static::forceDeleting(function ($term) {
            if (config('terms.settings.cascade_delete', false)) {
                $term->children()->forceDelete();
            } else {
                $term->children()->update(['parent_id' => null]);
            }
        });
    }

    /**
     * Check if term type is valid
     */
    protected static function isValidType(?string $type): bool
    {
        if (empty($type)) {
            return false;
        }

        return in_array($type, config('terms.types', []));
    }

    /**
     * Get term type label
     */
    public function getTypeLabel(): ?string
    {
        return config("terms.types.{$this->type}");
    }

    /**
     * Get all valid term types
     */
    public static function getValidTypes(): array
    {
        return config('terms.types', []);
    }

    /**
     * Validate meta keys against reserved keys
     *
     * @param array $meta
     * @throws \Exception
     */
    protected static function validateMetaKeys(array $meta): void
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
}
