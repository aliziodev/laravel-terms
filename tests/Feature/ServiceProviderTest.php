<?php

declare(strict_types=1);

use Aliziodev\LaravelTerms\Contracts\TermsManagerInterface;
use Aliziodev\LaravelTerms\Enums\TermType;
use Aliziodev\LaravelTerms\Facades\Terms;
use Aliziodev\LaravelTerms\TermsManager;

it('binds the manager contract to the container', function (): void {
    $manager = app(TermsManagerInterface::class);

    expect($manager)->toBeInstanceOf(TermsManager::class)
        ->and($manager)->toBe(app('terms'));
});

it('resolves the same singleton instance every time', function (): void {
    expect(app('terms'))->toBe(app('terms'));
});

it('facade resolves to the same manager instance', function (): void {
    expect(Terms::getFacadeRoot())->toBe(app('terms'));
});

it('loads default config values', function (): void {
    expect(config('terms.morph_type'))->toBe('numeric')
        ->and(config('terms.table_names.terms'))->toBe('terms')
        ->and(config('terms.table_names.termables'))->toBe('termables')
        ->and(config('terms.types'))->toContain(TermType::Tag->value)
        ->and(config('terms.slugs.generate'))->toBeTrue()
        ->and(config('terms.slugs.regenerate_on_update'))->toBeFalse();
});
