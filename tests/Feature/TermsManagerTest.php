<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('ignores empty names when syncing terms', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->syncTerms(['Sale', '', null], TermType::Tag);

    expect(Term::query()->where('type', TermType::Tag->value)->count())->toBe(1)
        ->and($product->termsOfType(TermType::Tag)->count())->toBe(1);
});

it('can detach specific terms by type', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->syncTerms(['Sale', 'New'], TermType::Tag);
    $product->detachTerms(['Sale'], TermType::Tag);

    expect($product->termsOfType(TermType::Tag)->count())->toBe(1)
        ->and($product->termsOfType(TermType::Tag)->first()->slug)->toBe('new');
});

it('can detach all terms when no names are provided', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->syncTerms(['Sale', 'New'], TermType::Tag);
    $product->detachTerms();

    expect($product->terms()->count())->toBe(0);
});

it('detach with type but no names only removes terms of that type', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->syncTerms(['Sale', 'New'], TermType::Tag);
    $product->syncTerms(['Nike'], TermType::Brand);

    // Should remove only tags, leave brand intact
    $product->detachTerms([], TermType::Tag);

    expect($product->termsOfType(TermType::Tag)->count())->toBe(0)
        ->and($product->termsOfType(TermType::Brand)->count())->toBe(1);
});

it('sync does not detach terms of a different type', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->syncTerms(['Sale'], TermType::Tag);
    $product->syncTerms(['Nike'], TermType::Brand);

    // Re-sync tags — brand must remain
    $product->syncTerms(['New'], TermType::Tag);

    expect($product->termsOfType(TermType::Tag)->count())->toBe(1)
        ->and($product->termsOfType(TermType::Tag)->first()->slug)->toBe('new')
        ->and($product->termsOfType(TermType::Brand)->count())->toBe(1);
});

it('attachTerms adds without detaching existing terms of the same type', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['Sale'], TermType::Tag);
    $product->attachTerms(['New'], TermType::Tag);

    expect($product->termsOfType(TermType::Tag)->count())->toBe(2);
});

it('attachTerms is idempotent — does not duplicate pivot rows', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['Sale'], TermType::Tag);
    $product->attachTerms(['Sale'], TermType::Tag);

    expect($product->termsOfType(TermType::Tag)->count())->toBe(1);
});

it('findOrCreate throws when name cannot be slugified', function (): void {
    app('terms')->findOrCreate('!!!', TermType::Tag);
})->throws(InvalidArgumentException::class);

it('findOrCreate accepts explicit slug that overrides generation', function (): void {
    $term = app('terms')->findOrCreate('中文', TermType::Tag, ['slug' => 'chinese']);

    expect($term->slug)->toBe('chinese')
        ->and($term->name)->toBe('中文');
});
