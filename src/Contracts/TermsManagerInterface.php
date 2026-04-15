<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Contracts;

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface TermsManagerInterface
{
    public function findOrCreate(string $name, TermType|string $type, array $attributes = []): Term;

    public function findOrCreateMany(array $names, TermType|string $type): Collection;

    public function attach(Model $model, array $names, TermType|string $type, ?string $context = null): void;

    public function sync(Model $model, array $names, TermType|string $type, ?string $context = null): void;

    /**
     * Detach terms from a model.
     *
     * - Pass $names to remove specific terms (matched by slug).
     * - Pass $type to restrict removal to one type.
     * - Pass $context to restrict removal to one pivot context only.
     * - Pass nothing to remove all pivot rows for the model.
     */
    public function detach(
        Model $model,
        array $names = [],
        TermType|string|null $type = null,
        ?string $context = null,
    ): void;
}
