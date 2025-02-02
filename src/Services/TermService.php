<?php

namespace Aliziodev\LaravelTerms\Services;

use Aliziodev\LaravelTerms\Contracts\TermInterface;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Aliziodev\LaravelTerms\Exceptions\{
    TermException,
    TermValidationException,
    TermNotFoundException,
    TermHierarchyException,
    TermCacheException,
    TermOrderException
};
use InvalidArgumentException;
use Exception;

class TermService implements TermInterface
{
    /**
     * Basic CRUD Operations
     */
    /**
     * @throws TermValidationException|TermException
     */
    public function create(array $attributes): Term
    {
        try {
            $this->validateTermAttributes($attributes);

            DB::beginTransaction();

            if (!isset($attributes['order'])) {
                $attributes['order'] = $this->getNextOrder($attributes['parent_id'] ?? null);
            }

            $term = Term::create($attributes);

            if (isset($attributes['meta'])) {
                $this->validateMetaData($attributes['meta']);
            }

            DB::commit();

            return $term;
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new TermValidationException($e->getMessage(), [], $attributes);
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to create term: " . $e->getMessage(), $attributes);
        }
    }

    /**
     * @throws TermNotFoundException|TermValidationException|TermHierarchyException|TermException
     */
    public function update(Term $term, array $attributes): Term
    {
        try {
            $this->validateTermAttributes($attributes, $term);

            DB::beginTransaction();

            if (isset($attributes['parent_id']) && $attributes['parent_id'] !== $term->parent_id) {
                $parent = Term::findOrFail($attributes['parent_id']);
                $this->validateHierarchy($term, $parent);
                $attributes['order'] = $this->getNextOrder($attributes['parent_id']);
            }

            if (isset($attributes['meta'])) {
                $this->validateMetaData($attributes['meta']);
            }

            $term->update($attributes);

            DB::commit();

            return $term;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new TermNotFoundException("Term not found", $term->id);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new TermValidationException($e->getMessage(), [], $term);
        } catch (TermHierarchyException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to update term: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermException
     */
    public function delete(Term $term): bool
    {
        try {
            DB::beginTransaction();
            $result = $term->delete();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to delete term: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermCacheException
     */
    public function all(): Collection
    {
        try {
            return Term::all();
        } catch (Exception $e) {
            throw new TermCacheException("Failed to retrieve all terms: " . $e->getMessage());
        }
    }

    /**
     * Query Methods
     */
    /**
     * @throws TermCacheException
     */
    public function getByType(string $type): Collection
    {
        try {
            return Term::where('type', $type)->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermCacheException("Failed to get terms by type: " . $e->getMessage());
        }
    }

    /**
     * @throws TermNotFoundException
     */
    public function getBySlug(string $slug): ?Term
    {
        try {
            return Term::where('slug', $slug)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new TermNotFoundException("Term not found with slug: {$slug}");
        }
    }

    /**
     * @throws TermNotFoundException
     */
    public function getById(int $id): ?Term
    {
        try {
            return Term::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new TermNotFoundException("Term not found with ID: {$id}");
        }
    }

    /**
     * Hierarchy Methods
     */
    public function getTree(?string $type = null): Collection
    {
        $query = Term::whereNull('parent_id')->with('children');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('order')->get();
    }

    public function getFlat(?string $type = null): Collection
    {
        return $this->flattenTree($this->getTree($type));
    }

    /**
     * @throws TermHierarchyException
     */
    public function moveTo(Term $term, ?Term $parent = null): Term
    {
        try {
            DB::beginTransaction();

            if ($parent && !$term->isHierarchical()) {
                throw new TermHierarchyException("Term type {$term->type} does not support hierarchy", $term, $parent);
            }

            if ($parent && $this->wouldCreateCycle($term, $parent)) {
                throw new TermHierarchyException("Cannot move a term to its own descendant", $term, $parent);
            }

            $term->parent_id = $parent?->id;
            $term->save();

            DB::commit();
            return $term;
        } catch (TermHierarchyException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermHierarchyException("Failed to move term: " . $e->getMessage(), $term, $parent);
        }
    }

    public function syncOrder(): void
    {
        $types = array_keys(config('terms.types', []));

        foreach ($types as $type) {
            $position = 1;
            Term::where('type', $type)
                ->orderBy('order')
                ->each(function ($term) use (&$position) {
                    $term->update(['order' => $position++]);
                });
        }
    }

    /**
     * Helper Methods
     */
    protected function validateTermAttributes(array $attributes, ?Term $term = null): void
    {
        // Validate term type
        if (isset($attributes['type'])) {
            $validTypes = array_keys(config('terms.types', []));
            if (!empty($validTypes) && !in_array($attributes['type'], $validTypes)) {
                throw new InvalidArgumentException("Invalid term type: {$attributes['type']}");
            }
        }

        // Validate name length
        if (isset($attributes['name'])) {
            $nameValidation = config('terms.validation.name', []);
            $nameLength = mb_strlen($attributes['name']);

            if (isset($nameValidation['min']) && $nameLength < $nameValidation['min']) {
                throw new InvalidArgumentException("Name too short (minimum {$nameValidation['min']} characters)");
            }

            if (isset($nameValidation['max']) && $nameLength > $nameValidation['max']) {
                throw new InvalidArgumentException("Name too long (maximum {$nameValidation['max']} characters)");
            }
        }

        // Validate slug if provided
        if (isset($attributes['slug'])) {
            $slugValidation = config('terms.validation.slug', []);
            if (isset($slugValidation['pattern']) && !preg_match($slugValidation['pattern'], $attributes['slug'])) {
                throw new InvalidArgumentException('Invalid slug format');
            }
        }
    }

    protected function flattenTree(Collection $terms, int $level = 0): Collection
    {
        $result = collect();

        foreach ($terms as $term) {
            $term->level = $level;
            $result->push($term);

            if ($term->children->isNotEmpty()) {
                $result = $result->merge($this->flattenTree($term->children, $level + 1));
            }
        }

        return $result;
    }

    /**
     * Advanced CRUD Operations
     */
    /**
     * @throws TermException
     */
    public function createMany(array $terms): Collection
    {
        try {
            DB::beginTransaction();

            $createdTerms = collect($terms)->map(function ($attributes) {
                return $this->create($attributes);
            });

            DB::commit();
            return $createdTerms;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Bulk creation failed: " . $e->getMessage());
        }
    }

    /**
     * @throws TermValidationException|TermException
     */
    public function updateMany(array $terms): Collection
    {
        try {
            DB::beginTransaction();

            $updatedTerms = collect($terms)->map(function ($data) {
                $term = Term::findOrFail($data['id']);
                return $this->update($term, $data);
            });

            DB::commit();
            return $updatedTerms;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new TermNotFoundException("One or more terms not found");
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Bulk update failed: " . $e->getMessage());
        }
    }

    /**
     * @throws TermNotFoundException|TermException
     */
    public function restore(Term $term): Term
    {
        try {
            DB::beginTransaction();

            if (!$term->trashed()) {
                throw new InvalidArgumentException("Term is not deleted");
            }

            $term->restore();


            DB::commit();
            return $term;
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw new TermValidationException($e->getMessage(), [], $term);
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to restore term: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermException
     */
    public function forceDelete(Term $term): bool
    {
        try {
            DB::beginTransaction();

            $result = $term->forceDelete();


            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to force delete term: " . $e->getMessage(), $term);
        }
    }

    /**
     * Advanced Query Methods
     */
    /**
     * @throws TermNotFoundException|TermException
     */
    public function findBySlugAndType(string $slug, string $type): ?Term
    {
        try {
            return Term::where('slug', $slug)->where('type', $type)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new TermNotFoundException("Term not found with slug '{$slug}' and type '{$type}'");
        }
    }

    /**
     * @throws TermCacheException
     */
    public function search(string $keyword, ?string $type = null): Collection
    {
        try {
            $query = Term::where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });

            if ($type) {
                $query->where('type', $type);
            }

            return $query->get();
        } catch (Exception $e) {
            throw new TermException("Search failed: " . $e->getMessage());
        }
    }

    public function paginate(int $perPage = 15)
    {
        return Term::orderBy('order')->paginate($perPage);
    }

    /**
     * Advanced Hierarchy Methods
     */
    /**
     * @throws TermOrderException
     */
    public function moveAfter(Term $term, Term $target): Term
    {
        try {
            DB::beginTransaction();

            $this->validateHierarchy($term, $target->parent);
            $term->order = $target->order + 1;
            $this->reorderSiblings($term, $target->order + 1);
            $term->save();

            DB::commit();
            return $term;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermOrderException("Failed to move term after target: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermOrderException
     */
    public function moveBefore(Term $term, Term $target): Term
    {
        try {
            DB::beginTransaction();

            $this->validateHierarchy($term, $target->parent);
            $term->order = $target->order;
            $this->reorderSiblings($term, $target->order);
            $term->save();


            DB::commit();
            return $term;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermOrderException("Failed to move term before target: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermOrderException
     */
    public function moveToStart(Term $term): Term
    {
        try {
            DB::beginTransaction();

            $term->order = 1;
            $this->reorderSiblings($term, 1);
            $term->save();

            DB::commit();
            return $term;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermOrderException("Failed to move term to start: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermOrderException
     */
    public function moveToEnd(Term $term): Term
    {
        try {
            DB::beginTransaction();

            $maxOrder = Term::where('parent_id', $term->parent_id)
                ->where('type', $term->type)
                ->max('order');
            $term->order = $maxOrder + 1;
            $term->save();

            DB::commit();
            return $term;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermOrderException("Failed to move term to end: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermException
     */
    public function import(array $data): Collection
    {
        try {
            DB::beginTransaction();

            $imported = collect($data)->map(function ($item) {
                return $this->create($item);
            });

            DB::commit();
            return $imported;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Import failed: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function export(?string $type = null): array
    {
        try {
            $query = Term::with('children');

            if ($type) {
                $query->where('type', $type);
            }

            return $query->get()->map->toArray()->all();
        } catch (Exception $e) {
            throw new TermException("Export failed: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function getStats(): array
    {
        return [
            'total_terms' => Term::count(),
            'total_relationships' => DB::table('termables')->count(),
            'terms_by_type' => Term::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray()
        ];
    }

    /**
     * @throws TermException
     */
    public function getMostUsed(int $limit = 10): Collection
    {
        try {
            return Term::withCount('models')
                ->orderByDesc('models_count')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            throw new TermException("Failed to get most used terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    protected function wouldCreateCycle(Term $term, Term $parent): bool
    {
        try {
            if ($term->id === $parent->id) {
                return true;
            }
            return $parent->isDescendantOf($term);
        } catch (Exception $e) {
            throw new TermException("Failed to check hierarchy cycle: " . $e->getMessage());
        }
    }

    /**
     * Validation Methods
     */
    public function validateSlug(string $slug, ?string $type = null): bool
    {
        $query = Term::where('slug', $slug);

        if ($type) {
            $query->where('type', $type);
        }

        return !$query->exists();
    }

    public function validateHierarchy(Term $term, ?Term $parent = null): void
    {
        if (!$parent) {
            return;
        }

        if (!$this->isHierarchicalType($term->type)) {
            throw new TermHierarchyException("Term type {$term->type} does not support hierarchy");
        }

        if ($this->wouldCreateCycle($term, $parent)) {
            throw new TermValidationException("Cannot move a term to its own descendant");
        }

        // Check max depth
        $depth = $this->calculateDepth($parent) + 1;
        $maxDepth = config("terms.types.{$term->type}.max_depth", 0);

        if ($maxDepth > 0 && $depth > $maxDepth) {
            throw new TermValidationException("Maximum depth exceeded for term type {$term->type}");
        }
    }

    /**
     * Helper Methods
     */
    protected function reorderSiblings(Term $term, int $fromOrder): void
    {
        try {
            Term::where('parent_id', $term->parent_id)
                ->where('type', $term->type)
                ->where('id', '!=', $term->id)
                ->where('order', '>=', $fromOrder)
                ->increment('order');
        } catch (Exception $e) {
            throw new TermOrderException("Failed to reorder siblings: " . $e->getMessage());
        }
    }

    protected function calculateDepth(Term $term): int
    {
        $depth = 0;
        $current = $term;

        while ($current->parent_id) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }

    protected function isHierarchicalType(string $type): bool
    {
        return (bool) config("terms.types.{$type}.hierarchical", false);
    }

    /**
     * Additional Hierarchy Methods
     */
    /**
     * @throws TermHierarchyException
     */
    public function getChildren(Term $term): Collection
    {
        try {
            return $term->children()->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermHierarchyException("Failed to get term children: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermHierarchyException
     */
    public function getParent(Term $term): ?Term
    {
        try {
            return $term->parent;
        } catch (Exception $e) {
            throw new TermHierarchyException("Failed to get term parent: " . $e->getMessage(), $term);
        }
    }

    public function getRoots(?string $type = null): Collection
    {
        $query = Term::whereNull('parent_id');
        if ($type) {
            $query->where('type', $type);
        }
        return $query->orderBy('order')->get();
    }

    /**
     * @throws TermHierarchyException
     */
    public function getAncestors(Term $term): Collection
    {
        try {
            return $term->ancestors()->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermHierarchyException("Failed to get term ancestors: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermHierarchyException
     */
    public function getDescendants(Term $term): Collection
    {
        try {
            return $term->descendants()->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermHierarchyException("Failed to get term descendants: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermHierarchyException
     */
    public function getSiblings(Term $term): Collection
    {
        try {
            return $term->siblings()->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermHierarchyException("Failed to get term siblings: " . $e->getMessage(), $term);
        }
    }

    /**
     * @throws TermException
     */
    public function getByTypes(array $types): Collection
    {
        try {
            return Term::whereIn('type', $types)->orderBy('order')->get();
        } catch (Exception $e) {
            throw new TermException("Failed to get terms by types: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function getByMeta(string $key, $value): Collection
    {
        try {
            return Term::where("meta->{$key}", $value)->get();
        } catch (Exception $e) {
            throw new TermException("Failed to get terms by meta: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function countByType(string $type): int
    {
        try {
            return Term::where('type', $type)->count();
        } catch (Exception $e) {
            throw new TermException("Failed to count terms by type: " . $e->getMessage());
        }
    }

    /**
     * Additional Validation Methods
     */
    public function exists(string $slug): bool
    {
        return Term::where('slug', $slug)->exists();
    }

    /**
     * Advanced Meta Methods
     */
    public function updateMetaForType(string $type, array $meta): bool
    {
        $result = Term::where('type', $type)->update(['meta' => $meta]);
        return (bool) $result;
    }

    public function clearMetaForType(string $type): bool
    {
        $result = Term::where('type', $type)->update(['meta' => null]);
        return (bool) $result;
    }

    /**
     * Bulk Operations
     */
    /**
     * @throws TermException
     */
    public function bulkAttach(array $modelTerms): void
    {
        try {
            DB::transaction(function () use ($modelTerms) {
                foreach ($modelTerms as $modelId => $terms) {
                    if ($model = $this->findModel($modelId)) {
                        $model->attachTerms($terms);
                    }
                }
            });
        } catch (Exception $e) {
            throw new TermException("Failed to bulk attach terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function bulkDetach(array $modelTerms): void
    {
        try {
            DB::transaction(function () use ($modelTerms) {
                foreach ($modelTerms as $modelId => $terms) {
                    if ($model = $this->findModel($modelId)) {
                        $model->detachTerms($terms);
                    }
                }
            });
        } catch (Exception $e) {
            throw new TermException("Failed to bulk detach terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function bulkSync(array $modelTerms): void
    {
        try {
            DB::transaction(function () use ($modelTerms) {
                foreach ($modelTerms as $modelId => $terms) {
                    if ($model = $this->findModel($modelId)) {
                        $model->syncTerms($terms);
                    }
                }
            });
        } catch (Exception $e) {
            throw new TermException("Failed to bulk sync terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function deleteMany(array $ids): bool
    {
        try {
            DB::beginTransaction();

            $result = Term::whereIn('id', $ids)->delete();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to delete multiple terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function restoreMany(array $ids): bool
    {
        try {
            DB::beginTransaction();

            $result = Term::withTrashed()->whereIn('id', $ids)->restore();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to restore multiple terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    public function forceDeleteMany(array $ids): bool
    {
        try {
            DB::beginTransaction();

            $result = Term::withTrashed()->whereIn('id', $ids)->forceDelete();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermException("Failed to force delete multiple terms: " . $e->getMessage());
        }
    }

    /**
     * @throws TermException
     */
    protected function findModel($modelId)
    {
        try {
            $modelClass = config('terms.model');
            return $modelClass ? $modelClass::find($modelId) : null;
        } catch (Exception $e) {
            throw new TermException("Failed to find model: " . $e->getMessage());
        }
    }

    /**
     * Advanced Statistics
     */
    public function getUsageStats(): array
    {
        return [
            'total_terms' => Term::count(),
            'total_types' => Term::distinct('type')->count(),
            'terms_by_type' => Term::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->all(),
            'terms_with_meta' => Term::whereNotNull('meta')->count(),
            'terms_in_hierarchy' => Term::whereNotNull('parent_id')->count(),
            'root_terms' => Term::whereNull('parent_id')->count(),
            'leaf_terms' => Term::whereDoesntHave('children')->count(),
            'max_depth' => $this->getMaxDepthInHierarchy(),
            'most_used_types' => $this->getMostUsedTypes(),
        ];
    }

    public function getMetaStats(): array
    {

        $terms = Term::whereNotNull('meta')->get();
        $metaKeys = collect();
        $metaValues = collect();

        $terms->each(function ($term) use ($metaKeys, $metaValues) {
            collect($term->meta)->each(function ($value, $key) use ($metaKeys, $metaValues) {
                $metaKeys->push($key);
                $metaValues->push($value);
            });
        });

        return [
            'total_terms_with_meta' => $terms->count(),
            'unique_meta_keys' => $metaKeys->unique()->values()->all(),
            'most_used_meta_keys' => $metaKeys->countBy()->sortDesc()->take(10)->all(),
            'meta_value_types' => $metaValues->map(fn($value) => gettype($value))->countBy()->all(),
        ];
    }

    public function getMostUsedTypes(int $limit = 10): array
    {
        return Term::selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'type')
            ->all();
    }

    /**
     * Helper Methods
     */
    public function getTypes(): Collection
    {
        return Term::distinct()->pluck('type');
    }

    /**
     * Get ordered terms
     */
    public function ordered()
    {
        return Term::orderBy('order');
    }

    /**
     * @throws TermOrderException
     */
    public function reorder(array $terms): void
    {
        try {
            DB::beginTransaction();

            foreach ($terms as $order => $termId) {
                Term::where('id', $termId)->update(['order' => $order + 1]);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new TermOrderException("Failed to reorder terms: " . $e->getMessage(), $terms);
        }
    }

    /**
     * Get max order within parent
     */
    protected function getMaxOrder(?int $parentId = null): int
    {
        return Term::where('parent_id', $parentId)->max('order') ?? 0;
    }

    /**
     * @throws TermOrderException
     */
    protected function getNextOrder(?int $parentId = null): int
    {
        try {
            $query = Term::query();

            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }

            return $query->max('order') + 1;
        } catch (Exception $e) {
            throw new TermOrderException("Failed to get next order: " . $e->getMessage());
        }
    }

    /**
     * Update term order
     */
    public function updateOrder(Term $term, int $order): Term
    {
        $term->order = $order;
        $term->save();
        return $term;
    }

    /**
     * @throws TermValidationException
     */
    protected function validateMetaData(array $meta): void
    {
        try {
            $metaValidation = config('terms.validation.meta', []);

            foreach ($meta as $key => $value) {
                // Validate key format
                if (isset($metaValidation['key_pattern']) && !preg_match($metaValidation['key_pattern'], $key)) {
                    throw new InvalidArgumentException("Invalid meta key format: {$key}");
                }

                // Validate key length
                if (isset($metaValidation['key_max_length']) && strlen($key) > $metaValidation['key_max_length']) {
                    throw new InvalidArgumentException("Meta key too long: {$key}");
                }

                // Validate value type
                if (isset($metaValidation['allowed_types'])) {
                    $type = gettype($value);
                    if (!in_array($type, $metaValidation['allowed_types'])) {
                        throw new InvalidArgumentException("Invalid meta value type for key {$key}: {$type}");
                    }
                }

                // Validate string value length if applicable
                if (is_string($value) && isset($metaValidation['value_max_length'])) {
                    if (strlen($value) > $metaValidation['value_max_length']) {
                        throw new InvalidArgumentException("Meta value too long for key: {$key}");
                    }
                }
            }
        } catch (InvalidArgumentException $e) {
            throw new TermValidationException($e->getMessage(), ['meta' => $meta]);
        } catch (Exception $e) {
            throw new TermValidationException("Meta validation failed: " . $e->getMessage(), ['meta' => $meta]);
        }
    }
}
