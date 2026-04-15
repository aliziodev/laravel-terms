<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('supports enum-backed built-in types', function (): void {
    $product = Product::create([
        'name' => 'Classic Shoes',
    ]);

    $product->syncTerms(['Sale'], TermType::Tag);
    $product->syncTerms(['Nike'], TermType::Brand);

    expect(Term::query()->where('type', TermType::Tag->value)->count())->toBe(1)
        ->and(Term::query()->where('type', TermType::Brand->value)->count())->toBe(1)
        ->and($product->termsOfType(TermType::Tag)->count())->toBe(1)
        ->and(Product::query()->whereHasTerm(TermType::Brand, 'nike')->count())->toBe(1);
});

it('supports custom string types', function (): void {
    $product = Product::create([
        'name' => 'Classic Shoes',
    ]);

    $product->attachTerms(['Waterproof'], 'material');

    expect(Term::query()->where('type', 'material')->count())->toBe(1)
        ->and($product->termsOfType('material')->count())->toBe(1);
});

it('enforces unique slug per type', function (): void {
    app('terms')->findOrCreate('Sale', TermType::Tag);
    app('terms')->findOrCreate('Sale', TermType::Brand);

    expect(Term::query()->where('slug', 'sale')->count())->toBe(2)
        ->and(Term::query()->where('type', TermType::Tag->value)->where('slug', 'sale')->count())->toBe(1)
        ->and(Term::query()->where('type', TermType::Brand->value)->where('slug', 'sale')->count())->toBe(1);
});
