<?php

declare(strict_types=1);

namespace Aliziodev\LaravelTerms\Enums;

enum TermType: string
{
    case Tag = 'tag';
    case Category = 'category';
    case Brand = 'brand';
    case Color = 'color';
    case Size = 'size';

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public static function toValue(self|string $type): string
    {
        return $type instanceof self ? $type->value : $type;
    }
}
