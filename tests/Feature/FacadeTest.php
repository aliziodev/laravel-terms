<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Facades\Terms;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('facade findOrCreate creates a term and returns it', function (): void {
    $term = Terms::findOrCreate('Summer', TermType::Tag);

    expect($term)->toBeInstanceOf(Term::class)
        ->and($term->slug)->toBe('summer')
        ->and($term->type)->toBe(TermType::Tag->value);
});

it('facade findOrCreate returns the same term on repeated calls', function (): void {
    $first = Terms::findOrCreate('Summer', TermType::Tag);
    $second = Terms::findOrCreate('Summer', TermType::Tag);

    expect($first->id)->toBe($second->id)
        ->and(Term::query()->count())->toBe(1);
});

it('facade findOrCreate accepts custom attributes', function (): void {
    $term = Terms::findOrCreate('Limited', TermType::Tag, [
        'sort_order' => 5,
    ]);

    expect($term->sort_order)->toBe(5);
});

it('facade findOrCreateMany returns a collection of terms', function (): void {
    $terms = Terms::findOrCreateMany(['New', 'Sale', ''], TermType::Tag);

    expect($terms)->toHaveCount(2) // empty string filtered out
        ->and($terms->pluck('slug')->sort()->values()->all())->toBe(['new', 'sale']);
});

it('facade attach and detach work correctly', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    Terms::attach($product, ['red', 'blue'], TermType::Color);
    expect($product->termsOfType(TermType::Color)->count())->toBe(2);

    Terms::detach($product, ['red'], TermType::Color);
    expect($product->termsOfType(TermType::Color)->count())->toBe(1)
        ->and($product->termsOfType(TermType::Color)->first()->slug)->toBe('blue');
});
