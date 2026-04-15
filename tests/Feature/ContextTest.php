<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Tests\Fixtures\Product;

it('stores context on the pivot when attaching terms', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['red'], TermType::Color, 'primary');

    $pivot = $product->terms()->first()?->pivot;

    expect($pivot)->not->toBeNull()
        ->and($pivot->context)->toBe('primary');
});

it('sync within a context does not affect terms attached under a different context', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['red', 'blue'], TermType::Color, 'primary');
    $product->attachTerms(['green'], TermType::Color, 'secondary');

    // Replace primary context — secondary must remain untouched
    $product->syncTerms(['yellow'], TermType::Color, 'primary');

    $primarySlugs = $product->termsOfType(TermType::Color)
        ->wherePivot('context', 'primary')
        ->pluck('slug')
        ->sort()
        ->values()
        ->all();

    $secondarySlugs = $product->termsOfType(TermType::Color)
        ->wherePivot('context', 'secondary')
        ->pluck('slug')
        ->all();

    expect($primarySlugs)->toBe(['yellow'])
        ->and($secondarySlugs)->toBe(['green']);
});

it('attach without context stores null on pivot', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['sale'], 'tag');

    expect($product->terms()->first()?->pivot?->context)->toBeNull();
});

it('two attachments with different contexts are treated as separate pivot rows', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['sale'], TermType::Tag, 'homepage');
    $product->attachTerms(['sale'], TermType::Tag, 'sidebar');

    expect($product->terms()->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Context-aware detach
// ---------------------------------------------------------------------------

it('detachTerms with context removes only pivot rows for that context', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['sale', 'new'], TermType::Tag, 'homepage');
    $product->attachTerms(['sale'], TermType::Tag, 'sidebar');

    // Remove all homepage terms — sidebar must remain
    $product->detachTerms([], TermType::Tag, 'homepage');

    $remaining = $product->terms()->get();

    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->slug)->toBe('sale')
        ->and($remaining->first()->pivot->context)->toBe('sidebar');
});

it('detachTerms with specific names and context removes only matching rows', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['sale', 'new'], TermType::Tag, 'homepage');
    $product->attachTerms(['sale'], TermType::Tag, 'sidebar');

    // Remove only 'sale' from homepage — 'new' in homepage and 'sale' in sidebar survive
    $product->detachTerms(['sale'], TermType::Tag, 'homepage');

    $homepageSlugs = $product->termsOfType(TermType::Tag)
        ->wherePivot('context', 'homepage')
        ->pluck('slug')
        ->all();

    $sidebarSlugs = $product->termsOfType(TermType::Tag)
        ->wherePivot('context', 'sidebar')
        ->pluck('slug')
        ->all();

    expect($homepageSlugs)->toBe(['new'])
        ->and($sidebarSlugs)->toBe(['sale']);
});

it('detachTerms without context removes across all contexts', function (): void {
    $product = Product::create(['name' => 'Classic Shoes']);

    $product->attachTerms(['sale'], TermType::Tag, 'homepage');
    $product->attachTerms(['sale'], TermType::Tag, 'sidebar');

    // Remove 'sale' tag regardless of context
    $product->detachTerms(['sale'], TermType::Tag);

    expect($product->terms()->count())->toBe(0);
});
