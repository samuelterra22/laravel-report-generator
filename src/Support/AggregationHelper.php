<?php

declare(strict_types=1);

namespace SamuelTerra22\ReportGenerator\Support;

class AggregationHelper
{
    public static function init(array $showTotalColumns): array
    {
        $state = [];
        foreach ($showTotalColumns as $column => $type) {
            $state[$column] = [
                'type' => $type,
                'sum' => 0,
                'count' => 0,
                'min' => null,
                'max' => null,
            ];
        }

        return $state;
    }

    public static function update(array &$state, string $column, mixed $value): void
    {
        if (! isset($state[$column])) {
            return;
        }

        $numericValue = (float) $value;

        $state[$column]['sum'] += $numericValue;
        $state[$column]['count']++;

        if ($state[$column]['min'] === null || $numericValue < $state[$column]['min']) {
            $state[$column]['min'] = $numericValue;
        }

        if ($state[$column]['max'] === null || $numericValue > $state[$column]['max']) {
            $state[$column]['max'] = $numericValue;
        }
    }

    public static function result(array $state, string $column): float
    {
        if (! isset($state[$column])) {
            return 0;
        }

        $entry = $state[$column];
        $type = $entry['type'];

        return match ($type) {
            'avg' => $entry['count'] > 0 ? $entry['sum'] / $entry['count'] : 0,
            'min' => $entry['min'] ?? 0,
            'max' => $entry['max'] ?? 0,
            'count' => (float) $entry['count'],
            default => $entry['sum'], // 'sum', 'point', or unknown
        };
    }

    public static function formatResult(array $state, string $column): string
    {
        $value = self::result($state, $column);
        $type = $state[$column]['type'] ?? 'sum';

        if ($type === 'point') {
            return number_format($value, 2, '.', ',');
        }

        if ($type === 'count') {
            return strtoupper($type).' '.number_format($value, 0, '.', ',');
        }

        return strtoupper($type).' '.number_format($value, 2, '.', ',');
    }

    public static function reset(array &$state): void
    {
        foreach ($state as $column => &$entry) {
            $entry['sum'] = 0;
            $entry['count'] = 0;
            $entry['min'] = null;
            $entry['max'] = null;
        }
    }
}
