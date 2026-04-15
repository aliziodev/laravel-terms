<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Models\Term;

it('generates a slug on create when slug is blank', function (): void {
    $term = Term::create([
        'name' => 'Fresh Arrival',
        'type' => TermType::Tag->value,
        'slug' => '',
    ]);

    expect($term->slug)->toBe('fresh-arrival');
});

it('keeps an explicit slug when it is provided', function (): void {
    $term = Term::create([
        'name' => 'Fresh Arrival',
        'type' => TermType::Tag->value,
        'slug' => 'custom-slug',
    ]);

    expect($term->slug)->toBe('custom-slug');
});

it('regenerates slug on update when enabled and slug is blank', function (): void {
    config()->set('terms.slugs.regenerate_on_update', true);

    $term = Term::create([
        'name' => 'Fresh Arrival',
        'type' => TermType::Tag->value,
        'slug' => '',
    ]);

    $term->slug = '';
    $term->name = 'Updated Arrival';
    $term->save();

    expect($term->slug)->toBe('updated-arrival');
});

it('does not regenerate slug on update when regenerate_on_update is false', function (): void {
    config()->set('terms.slugs.regenerate_on_update', false);

    $term = Term::create([
        'name' => 'Fresh Arrival',
        'type' => TermType::Tag->value,
        'slug' => '',
    ]);

    $original = $term->slug;

    $term->name = 'Updated Arrival';
    $term->save();

    expect($term->slug)->toBe($original);
});

it('throws when slug cannot be generated from a non-sluggable name on create', function (): void {
    Term::create([
        'name' => '!!!',
        'type' => TermType::Tag->value,
        // no explicit slug — triggers auto-generation
    ]);
})->throws(InvalidArgumentException::class);

it('throws when slug cannot be generated from a non-sluggable name on update', function (): void {
    config()->set('terms.slugs.regenerate_on_update', true);

    $term = Term::create([
        'name' => 'Valid Name',
        'type' => TermType::Tag->value,
        'slug' => '',
    ]);

    $term->slug = '';      // clear slug to trigger regeneration
    $term->name = '!!!';   // non-sluggable
    $term->save();
})->throws(InvalidArgumentException::class);
