<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms;

use Aliziodev\LaravelTerms\Contracts\TermsManagerInterface;
use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TermsManager implements TermsManagerInterface
{
    public function findOrCreate(string $name, TermType|string $type, array $attributes = []): Term
    {
        $type = $this->normalizeType($type);
        $slug = $attributes['slug'] ?? str($name)->slug()->toString();

        if (blank($slug)) {
            throw new \InvalidArgumentException(
                "Cannot generate a slug from term name [{$name}]. Provide an explicit slug via \$attributes['slug']."
            );
        }

        return $this->termModel()->newQuery()->firstOrCreate(
            ['type' => $type, 'slug' => $slug],
            array_merge($attributes, ['name' => $name, 'type' => $type, 'slug' => $slug])
        );
    }

    public function findOrCreateMany(array $names, TermType|string $type): Collection
    {
        $type = $this->normalizeType($type);

        return collect($names)
            ->filter(fn ($name): bool => filled($name))
            ->map(fn ($name): Term => $this->findOrCreate((string) $name, $type));
    }

    public function attach(Model $model, array $names, TermType|string $type, ?string $context = null): void
    {
        $terms = $this->findOrCreateMany($names, $type);

        if ($context === null) {
            // syncWithoutDetaching deduplicates by term_id — correct for null-context.
            // INSERT OR IGNORE cannot be used here because SQL UNIQUE constraints treat
            // each NULL as distinct, so the same row can be inserted multiple times.
            $model->terms()->syncWithoutDetaching(
                $terms->mapWithKeys(fn (Term $term): array => [
                    $term->getKey() => ['context' => null],
                ])->all()
            );
        } else {
            $this->insertPivotRows($model, $terms, $context);
        }
    }

    public function sync(Model $model, array $names, TermType|string $type, ?string $context = null): void
    {
        $type = $this->normalizeType($type);
        $terms = $this->findOrCreateMany($names, $type);
        $newTermIds = $terms->map(fn (Term $term) => $term->getKey())->all();
        $termsTable = $this->termModel()->getTable();

        // Use the raw query builder rather than the Eloquent relation to avoid
        // read-isolation issues when pivot rows were inserted via DB::table()
        // (which bypasses Eloquent's in-process relation cache).
        $pivotTable = config('terms.table_names.termables', 'termables');
        $existingTermIds = DB::table($pivotTable)
            ->join($termsTable, "{$termsTable}.id", '=', "{$pivotTable}.term_id")
            ->where("{$pivotTable}.termable_type", $model->getMorphClass())
            ->where("{$pivotTable}.termable_id", $model->getKey())
            ->where("{$termsTable}.type", $type)
            ->when($context !== null, fn ($q) => $q->where("{$pivotTable}.context", $context))
            ->pluck("{$pivotTable}.term_id")
            ->all();

        $toRemove = array_values(array_diff($existingTermIds, $newTermIds));

        if ($toRemove !== []) {
            $this->deletePivotRows($model, $toRemove, $context);
        }

        if ($context === null) {
            $model->terms()->syncWithoutDetaching(
                $terms->mapWithKeys(fn (Term $term): array => [
                    $term->getKey() => ['context' => null],
                ])->all()
            );
        } else {
            $this->insertPivotRows($model, $terms, $context);
        }
    }

    public function detach(
        Model $model,
        array $names = [],
        TermType|string|null $type = null,
        ?string $context = null,
    ): void {
        $type = $type === null ? null : $this->normalizeType($type);

        if ($names === [] && $type === null) {
            $this->deletePivotRows($model, [], $context);

            return;
        }

        $termQuery = $this->termModel()->newQuery();

        if ($type !== null) {
            $termQuery->where('type', $type);
        }

        if ($names !== []) {
            $slugs = collect($names)
                ->map(fn ($name): string => str((string) $name)->slug()->toString())
                ->all();
            $termQuery->whereIn('slug', $slugs);
        }

        $termIds = $termQuery->pluck('id')->all();
        $this->deletePivotRows($model, $termIds, $context);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Insert pivot rows using INSERT OR IGNORE so the unique constraint
     * (term_id, termable_type, termable_id, context) prevents duplicates.
     * Only call this when $context is a non-null string — null context requires
     * syncWithoutDetaching to avoid the SQL NULL-uniqueness pitfall.
     */
    protected function insertPivotRows(Model $model, Collection $terms, string $context): void
    {
        if ($terms->isEmpty()) {
            return;
        }

        $pivotTable = config('terms.table_names.termables', 'termables');
        $now = now();

        $rows = $terms->map(fn (Term $term): array => [
            'term_id' => $term->getKey(),
            'termable_type' => $model->getMorphClass(),
            'termable_id' => $model->getKey(),
            'context' => $context,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table($pivotTable)->insertOrIgnore($rows);
    }

    /**
     * Delete pivot rows for a model, optionally scoped by term IDs and context.
     * Passing an empty $termIds with no $context removes all pivot rows for the model.
     */
    protected function deletePivotRows(Model $model, array $termIds, ?string $context = null): void
    {
        $query = DB::table(config('terms.table_names.termables', 'termables'))
            ->where('termable_type', $model->getMorphClass())
            ->where('termable_id', $model->getKey());

        if ($termIds !== []) {
            $query->whereIn('term_id', $termIds);
        }

        if ($context !== null) {
            $query->where('context', $context);
        }

        $query->delete();
    }

    protected function termModel(): Term
    {
        $model = config('terms.model', Term::class);

        return new $model;
    }

    protected function normalizeType(TermType|string $type): string
    {
        return TermType::toValue($type);
    }
}
