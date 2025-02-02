<?php

namespace Aliziodev\LaravelTerms\Contracts;

use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Support\Collection;

interface TermInterface
{
    /**
     * Basic CRUD Operations
     * Handles basic term creation, reading, updating and soft deletion
     */
    public function create(array $attributes): Term;
    public function update(Term $term, array $attributes): Term;
    public function delete(Term $term): bool;
    public function all(): Collection;

    /**
     * Advanced CRUD Operations
     * Handles bulk creation, updates, restoration and permanent deletion
     */
    public function createMany(array $terms): Collection;
    public function updateMany(array $terms): Collection;
    public function restore(Term $term): Term;
    public function forceDelete(Term $term): bool;

    /**
     * Query Methods
     * Various methods for retrieving terms based on different criteria
     */
    public function getByType(string $type): Collection;
    public function getBySlug(string $slug): ?Term;
    public function getById(int $id): ?Term;
    public function search(string $keyword, ?string $type = null): Collection;
    public function paginate(int $perPage = 15);
    public function getTypes(): Collection;
    public function getByTypes(array $types): Collection;

    /**
     * Hierarchy Methods
     * Manages term hierarchical relationships and tree structure
     */
    public function getTree(?string $type = null): Collection;
    public function getFlat(?string $type = null): Collection;
    public function moveTo(Term $term, ?Term $parent = null): Term;
    public function getChildren(Term $term): Collection;
    public function getParent(Term $term): ?Term;
    public function getRoots(?string $type = null): Collection;
    public function getAncestors(Term $term): Collection;
    public function getDescendants(Term $term): Collection;
    public function getSiblings(Term $term): Collection;
    public function moveAfter(Term $term, Term $target): Term;
    public function moveBefore(Term $term, Term $target): Term;
    public function moveToStart(Term $term): Term;
    public function moveToEnd(Term $term): Term;

    /**
     * Meta Methods
     * Handles term metadata operations and queries
     */
    public function updateMetaForType(string $type, array $meta): bool;
    public function clearMetaForType(string $type): bool;
    public function getByMeta(string $key, $value): Collection;

    /**
     * Bulk Operations
     * Handles operations involving multiple terms or models simultaneously
     */
    public function bulkAttach(array $modelTerms): void;
    public function bulkDetach(array $modelTerms): void;
    public function bulkSync(array $modelTerms): void;
    public function deleteMany(array $ids): bool;
    public function restoreMany(array $ids): bool;
    public function forceDeleteMany(array $ids): bool;

    /**
     * Statistics Methods
     * Provides various statistical information about terms
     */
    public function getUsageStats(): array;
    public function getMetaStats(): array;
    public function getMostUsedTypes(int $limit = 10): array;
    public function countByType(string $type): int;

    /**
     * Order Methods
     * Manages term ordering and reordering operations
     */
    public function ordered();
    public function reorder(array $terms): void;
    public function updateOrder(Term $term, int $order): Term;
    public function syncOrder(): void;

}