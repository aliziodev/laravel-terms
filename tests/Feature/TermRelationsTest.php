<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('can sync and query terms by type', function (): void {
    $product = Product::create([
        'name' => 'Classic Shoes',
    ]);

    $product->syncTerms(['Sale', 'New'], 'tag');
    $product->syncTerms(['Nike'], 'brand');

    expect($product->terms()->count())->toBe(3)
        ->and(Term::query()->where('type', 'tag')->count())->toBe(2)
        ->and(Product::query()->whereHasTerm('brand', 'nike')->count())->toBe(1);
});
