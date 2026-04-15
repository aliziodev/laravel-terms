<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

// ---------------------------------------------------------------------------
// hasTerm
// ---------------------------------------------------------------------------

it('hasTerm returns true when the term is attached', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);
    $product->attachTerms(['sale'], TermType::Tag);

    expect($product->hasTerm(TermType::Tag, 'sale'))->toBeTrue();
});

it('hasTerm returns false when the term is not attached', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);
    $product->attachTerms(['sale'], TermType::Tag);

    expect($product->hasTerm(TermType::Tag, 'new'))->toBeFalse();
});

it('hasTerm returns false when the right slug but wrong type is attached', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);
    $product->attachTerms(['sale'], TermType::Tag);

    // 'sale' is a tag, not a brand
    expect($product->hasTerm(TermType::Brand, 'sale'))->toBeFalse();
});

it('hasTerm works with plain string types', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);
    $product->attachTerms(['waterproof'], 'material');

    expect($product->hasTerm('material', 'waterproof'))->toBeTrue()
        ->and($product->hasTerm('material', 'breathable'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// scopeWhereHasTerms — AND logic (default)
// ---------------------------------------------------------------------------

it('whereHasTerms with AND returns models that have all slugs', function (): void {
    $a = Product::create(['name' => 'A']);
    $b = Product::create(['name' => 'B']);
    $c = Product::create(['name' => 'C']);

    $a->attachTerms(['new', 'sale'], TermType::Tag);   // both
    $b->attachTerms(['new'], TermType::Tag);            // only one
    $c->attachTerms(['sale'], TermType::Tag);           // only one

    $results = Product::whereHasTerms(TermType::Tag, ['new', 'sale'])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('A');
});

it('whereHasTerms with AND returns empty when no model has all slugs', function (): void {
    $a = Product::create(['name' => 'A']);
    $a->attachTerms(['new'], TermType::Tag);

    $results = Product::whereHasTerms(TermType::Tag, ['new', 'sale'])->get();

    expect($results)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// scopeWhereHasTerms — OR logic
// ---------------------------------------------------------------------------

it('whereHasTerms with OR returns models that have any of the slugs', function (): void {
    $a = Product::create(['name' => 'A']);
    $b = Product::create(['name' => 'B']);
    $c = Product::create(['name' => 'C']);

    $a->attachTerms(['new'], TermType::Tag);
    $b->attachTerms(['sale'], TermType::Tag);
    $c->attachTerms(['featured'], TermType::Tag); // neither

    $results = Product::whereHasTerms(TermType::Tag, ['new', 'sale'], 'or')
        ->orderBy('name')
        ->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->all())->toBe(['A', 'B']);
});

it('whereHasTerms does not match terms of a different type', function (): void {
    $a = Product::create(['name' => 'A']);
    $a->attachTerms(['sale'], TermType::Tag);
    $a->attachTerms(['sale'], TermType::Brand); // same slug, different type

    // Searching brands — only the brand attachment qualifies
    $tagResults = Product::whereHasTerms(TermType::Tag, ['sale'])->get();
    $brandResults = Product::whereHasTerms(TermType::Brand, ['sale'])->get();

    expect($tagResults)->toHaveCount(1)
        ->and($brandResults)->toHaveCount(1);
});
