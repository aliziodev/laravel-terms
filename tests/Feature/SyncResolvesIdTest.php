<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('resolves integer ids, numeric strings, Term instances, and names during sync', function (): void {
    $term1 = Term::create(['name' => 'First', 'type' => 'tag']);
    $term2 = Term::create(['name' => 'Second', 'type' => 'tag']);

    $product = Product::create(['name' => 'Test Product']);

    // Sync with:
    // 1. Integer ID ($term1->id)
    // 2. String ID ((string) $term2->id)
    // 3. Model instance ($term1 again, should deduplicate)
    // 4. String name ('Third')
    $product->syncTerms([$term1->id, (string) $term2->id, $term1, 'Third'], 'tag');

    $terms = $product->terms()->orderBy('id')->get();

    expect($terms)->toHaveCount(3);
    expect($terms[0]->name)->toBe('First');
    expect($terms[1]->name)->toBe('Second');
    expect($terms[2]->name)->toBe('Third');
});
