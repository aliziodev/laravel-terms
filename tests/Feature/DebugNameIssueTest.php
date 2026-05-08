<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('eager loads terms correctly', function (): void {
    $product = Product::create([
        'name' => 'Classic Shoes',
    ]);

    $product->syncTerms(['Sale', 'New'], 'tag');

    $loaded = Product::with('terms')->first();

    foreach ($loaded->terms as $term) {
        dump('ID: '.$term->id.' Name: '.$term->name.' Slug: '.$term->slug);
    }

    expect($loaded->terms->first()->name)->toBe('Sale');
});
