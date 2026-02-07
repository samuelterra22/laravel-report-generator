<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Support;

class ColumnFormatter
{
    public static function format(mixed $value, string $type, array $options = []): string
    {
        return match ($type) {
            'currency' => self::formatCurrency($value, $options),
            'number' => self::formatNumber($value, $options),
            'date' => self::formatDate($value, $options),
            'datetime' => self::formatDateTime($value, $options),
            'percentage' => self::formatPercentage($value, $options),
            'boolean' => self::formatBoolean($value, $options),
            default => (string) $value,
        };
    }

    public static function formatCurrency(mixed $value, array $options = []): string
    {
        $prefix = $options['prefix'] ?? '$';
        $decimals = $options['decimals'] ?? 2;
        $decimalSeparator = $options['decimal_separator'] ?? '.';
        $thousandsSeparator = $options['thousands_separator'] ?? ',';

        return $prefix.number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatNumber(mixed $value, array $options = []): string
    {
        $decimals = $options['decimals'] ?? 0;
        $decimalSeparator = $options['decimal_separator'] ?? '.';
        $thousandsSeparator = $options['thousands_separator'] ?? ',';

        return number_format((float) $value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function formatDate(mixed $value, array $options = []): string
    {
        $format = $options['format'] ?? 'Y-m-d';

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        if (is_string($value) && $value !== '') {
            $date = new \DateTime($value);

            return $date->format($format);
        }

        return (string) $value;
    }

    public static function formatDateTime(mixed $value, array $options = []): string
    {
        $format = $options['format'] ?? 'Y-m-d H:i:s';

        return self::formatDate($value, ['format' => $format]);
    }

    public static function formatPercentage(mixed $value, array $options = []): string
    {
        $decimals = $options['decimals'] ?? 2;
        $suffix = $options['suffix'] ?? '%';

        return number_format((float) $value, $decimals).$suffix;
    }

    public static function formatBoolean(mixed $value, array $options = []): string
    {
        $trueLabel = $options['true'] ?? 'Yes';
        $falseLabel = $options['false'] ?? 'No';

        return $value ? $trueLabel : $falseLabel;
    }
}
