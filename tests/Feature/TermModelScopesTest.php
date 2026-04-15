<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('scopeType filters terms by type', function (): void {
    Term::create(['name' => 'Sale', 'type' => TermType::Tag->value, 'slug' => 'sale']);
    Term::create(['name' => 'Nike', 'type' => TermType::Brand->value, 'slug' => 'nike']);
    Term::create(['name' => 'Puma', 'type' => TermType::Brand->value, 'slug' => 'puma']);

    expect(Term::query()->type(TermType::Tag->value)->count())->toBe(1)
        ->and(Term::query()->type(TermType::Brand->value)->count())->toBe(2);
});

it('scopeSlug filters terms by slug', function (): void {
    Term::create(['name' => 'Sale', 'type' => TermType::Tag->value, 'slug' => 'sale']);
    Term::create(['name' => 'Nike', 'type' => TermType::Brand->value, 'slug' => 'nike']);

    $result = Term::query()->slug('sale')->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Sale');
});

it('scopeOrdered sorts terms ascending by sort_order by default', function (): void {
    Term::create(['name' => 'C', 'type' => TermType::Tag->value, 'slug' => 'c', 'sort_order' => 30]);
    Term::create(['name' => 'A', 'type' => TermType::Tag->value, 'slug' => 'a', 'sort_order' => 10]);
    Term::create(['name' => 'B', 'type' => TermType::Tag->value, 'slug' => 'b', 'sort_order' => 20]);

    $names = Term::query()->ordered()->pluck('name')->all();

    expect($names)->toBe(['A', 'B', 'C']);
});

it('scopeOrdered accepts a direction', function (): void {
    Term::create(['name' => 'A', 'type' => TermType::Tag->value, 'slug' => 'a', 'sort_order' => 10]);
    Term::create(['name' => 'B', 'type' => TermType::Tag->value, 'slug' => 'b', 'sort_order' => 20]);

    $names = Term::query()->ordered('desc')->pluck('name')->all();

    expect($names)->toBe(['B', 'A']);
});

it('termables relation returns models attached to the term', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);
    $product->syncTerms(['sale'], TermType::Tag);

    $term = Term::query()->where('slug', 'sale')->first();
    $attached = $term->termables(Product::class)->get();

    expect($attached)->toHaveCount(1)
        ->and($attached->first()->name)->toBe('Classic Shoes');
});
