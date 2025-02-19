<?php

namespace Aliziodev\LaravelTerms\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait TermScope
{
    /**
     * Basic Query Scopes
     */
    public function scopeSearch($query, string $keyword): Builder
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
        });
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLeaf(Builder $query): Builder
    {
        return $query->doesntHave('children');
    }

    /**
     * Tree Relations
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id')->ordered();
    }

    /**
     * Tree Query Methods
     */
    public static function tree(?string $type = null): Collection
    {
        $query = static::query()->root()->ordered();

        if ($type) {
            $query->where('type', $type);
        }

        return $query->with('children.children')->get();
    }

    public static function treeFlat(?string $type = null): Collection
    {
        $query = static::query()->ordered();

        if ($type) {
            $query->where('type', $type);
        }

        $terms = $query->get();
        $flatTree = collect();

        $flatten = function ($terms, $level = 0) use (&$flatten, &$flatTree) {
            foreach ($terms as $term) {
                $term->level = $level;
                $flatTree->push($term);

                if ($term->children->isNotEmpty()) {
                    $flatten($term->children, $level + 1);
                }
            }
        };

        $rootTerms = $terms->whereNull('parent_id');
        $flatten($rootTerms);

        return $flatTree;
    }

    /**
     * Tree Navigation Methods
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    public function getDescendants(): Collection
    {
        return static::query()
            ->whereIn('id', $this->getAllDescendantIds())
            ->ordered()
            ->get();
    }

    protected function getAllDescendantIds(): array
    {
        $ids = collect();

        $this->children->each(function ($child) use (&$ids) {
            $ids->push($child->id);
            $ids = $ids->merge($child->getAllDescendantIds());
        });

        return $ids->unique()->values()->all();
    }

    /**
     * Tree Attributes
     */
    public function getPathAttribute(): string
    {
        return $this->getAncestors()
            ->reverse()
            ->push($this)
            ->pluck('slug')
            ->implode('/');
    }

    public function getDepthAttribute(): int
    {
        return $this->getAncestors()->count();
    }

    public function getIsLeafAttribute(): bool
    {
        return !$this->children()->exists();
    }

    public function getIsRootAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Tree Format Methods
     */
    public function toTree(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'path' => $this->path,
            'depth' => $this->depth,
            'is_leaf' => $this->is_leaf,
            'is_root' => $this->is_root,
            'meta' => $this->meta,
            'children' => $this->children->map->toTree()
        ];
    }

    /**
     * Ordering Methods
     */
    protected static function bootTermScope()
    {
        static::creating(function ($term) {
            if (is_null($term->order)) {
                $term->order = static::query()
                    ->where('type', $term->type)
                    ->where('parent_id', $term->parent_id)
                    ->max('order') + 1;
            }
        });
    }

    public function moveToOrder(int $order): self
    {
        DB::transaction(function () use ($order) {
            $oldOrder = $this->order;

            if ($order > $oldOrder) {
                static::query()
                    ->where('type', $this->type)
                    ->where('parent_id', $this->parent_id)
                    ->whereBetween('order', [$oldOrder + 1, $order])
                    ->decrement('order');
            } else {
                static::query()
                    ->where('type', $this->type)
                    ->where('parent_id', $this->parent_id)
                    ->whereBetween('order', [$order, $oldOrder - 1])
                    ->increment('order');
            }

            $this->update(['order' => $order]);
        });

        return $this;
    }

    public function moveToStart(): self
    {
        return $this->moveToOrder(1);
    }

    public function moveToEnd(): self
    {
        $maxOrder = static::query()
            ->where('type', $this->type)
            ->where('parent_id', $this->parent_id)
            ->max('order');

        return $this->moveToOrder($maxOrder + 1);
    }

    public function moveBefore($otherTerm): self
    {
        if ($otherTerm) {
            return $this->moveToOrder($otherTerm->order);
        }
        return $this;
    }

    public function moveAfter($otherTerm): self
    {
        if ($otherTerm) {
            return $this->moveToOrder($otherTerm->order + 1);
        }
        return $this;
    }

    /**
     * Validation Methods
     */
    protected function preventCircularReference(): void
    {
        if (!$this->parent_id) {
            return;
        }

        $parent = $this->parent;
        while ($parent) {
            if ($parent->id === $this->id) {
                throw new \Exception('Circular reference detected in term hierarchy');
            }
            $parent = $parent->parent;
        }
    }

    /**
     * Meta Management Methods
     */
    public function setMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta ?? [], $meta);
        $this->save();
        return $this;
    }

    public function updateMeta(array $meta): self
    {
        $this->meta = $meta;
        $this->save();
        return $this;
    }

    public function getMeta(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->meta ?? [];
        }
        return data_get($this->meta, $key, $default);
    }

    public function removeMeta(string $key): self
    {
        $meta = $this->meta ?? [];
        unset($meta[$key]);
        $this->meta = $meta;
        $this->save();
        return $this;
    }

    /**
     * Static Meta Methods
     */
    public static function updateMetaForType(string $type, array $meta): void
    {
        static::query()
            ->where('type', $type)
            ->update(['meta' => DB::raw("meta || '" . json_encode($meta) . "'::jsonb")]);
    }
}
