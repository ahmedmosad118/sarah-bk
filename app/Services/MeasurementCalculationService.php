<?php

namespace App\Services;

class MeasurementCalculationService
{
    /**
     * Supported units and their strictly corresponding canonical measurement types.
     */
    public const UNIT_TO_TYPE = [
        'm2' => 'area',
        'm²' => 'area',
        'sqm' => 'area',
        'm3' => 'volume',
        'm³' => 'volume',
        'cum' => 'volume',
        'lm' => 'linear',
        'rm' => 'linear',
        'pcs' => 'count',
        'item' => 'count',
        'unit' => 'count',
        // Also allow canonical type names as unit aliases if needed
        'area' => 'area',
        'volume' => 'volume',
        'linear' => 'linear',
        'count' => 'count',
    ];

    public const TYPE_TO_CANONICAL_UNIT = [
        'area' => 'm2',
        'volume' => 'm3',
        'linear' => 'lm',
        'count' => 'pcs',
    ];

    /**
     * Legacy UNITS array alias for backward compatibility.
     */
    public const UNITS = [
        'm2' => 'area',
        'm3' => 'volume',
        'lm' => 'linear',
        'pcs' => 'count',
    ];

    /**
     * Determine canonical measurement type from unit string.
     *
     * @throws \InvalidArgumentException If unit is unrecognized.
     */
    public static function resolveType(string $unit): string
    {
        $normalized = strtolower(trim($unit));
        if (!isset(self::UNIT_TO_TYPE[$normalized])) {
            throw new \InvalidArgumentException("Invalid or unsupported measurement unit '{$unit}'. Supported units: m2, m3, lm, pcs.");
        }
        return self::UNIT_TO_TYPE[$normalized];
    }

    /**
     * Strictly validate that unit and measurement_type are compatible.
     *
     * Expected mapping:
     * m² / m2  -> AREA
     * m³ / m3  -> VOLUME
     * lm       -> LINEAR
     * pcs      -> COUNT
     *
     * Contradictory combinations (e.g. m2 + volume, m3 + area, lm + count, pcs + area) are strictly rejected.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateUnitAndType(string $unit, ?string $type = null): array
    {
        $normalizedUnit = strtolower(trim($unit));
        if (!isset(self::UNIT_TO_TYPE[$normalizedUnit])) {
            throw new \InvalidArgumentException("Invalid or unsupported measurement unit '{$unit}'. Supported units: m2, m3, lm, pcs.");
        }

        $expectedType = self::UNIT_TO_TYPE[$normalizedUnit];
        $canonicalUnit = self::TYPE_TO_CANONICAL_UNIT[$expectedType] ?? $normalizedUnit;

        if ($type !== null && trim($type) !== '') {
            $normalizedType = strtolower(trim($type));
            $resolvedType = self::UNIT_TO_TYPE[$normalizedType] ?? (isset(self::TYPE_TO_CANONICAL_UNIT[$normalizedType]) ? $normalizedType : null);

            if ($resolvedType === null || $resolvedType !== $expectedType) {
                throw new \InvalidArgumentException("Unit '{$unit}' is incompatible with measurement type '{$type}'. Expected type: '{$expectedType}'.");
            }
        }

        return [
            'unit' => $canonicalUnit,
            'measurement_type' => $expectedType,
        ];
    }

    /**
     * Calculate single item gross and net quantities.
     *
     * @param array $item
     * @return array Calculated item values
     * @throws \InvalidArgumentException
     */
    public function calculateItem(array $item): array
    {
        $validated = self::validateUnitAndType(
            $item['unit'] ?? 'm2',
            $item['measurement_type'] ?? null
        );

        $unit = $validated['unit'];
        $type = $validated['measurement_type'];

        $count = isset($item['count']) && $item['count'] !== null ? (float) $item['count'] : 1.0;
        $length = isset($item['length']) && $item['length'] !== null ? (float) $item['length'] : 0.0;
        $width = isset($item['width']) && $item['width'] !== null ? (float) $item['width'] : 0.0;
        $height = isset($item['height']) && $item['height'] !== null ? (float) $item['height'] : 0.0;
        $deductions = isset($item['deductions']) && $item['deductions'] !== null ? (float) $item['deductions'] : 0.0;

        $gross = 0.0;

        switch ($type) {
            case 'volume':
            case 'm3':
                $gross = $count * $length * $width * $height;
                break;

            case 'linear':
            case 'lm':
                $gross = $count * $length;
                break;

            case 'count':
            case 'pcs':
                $gross = $count;
                break;

            case 'area':
            case 'm2':
            default:
                $type = 'area';
                $gross = $count * $length * $width;
                break;
        }

        $net = max(0.0, $gross - $deductions);

        // Direct quantity override if dimensions are not provided but direct net_quantity is entered
        if ($gross == 0.0 && isset($item['net_quantity']) && (float) $item['net_quantity'] > 0) {
            $net = (float) $item['net_quantity'];
            $gross = $net + $deductions;
        }

        return [
            'room_name' => trim((string) ($item['room_name'] ?? '')),
            'item_name' => trim((string) ($item['item_name'] ?? '')),
            'unit' => $unit,
            'measurement_type' => $type,
            'count' => round($count, 2),
            'length' => isset($item['length']) && $item['length'] !== null ? round($length, 2) : null,
            'width' => isset($item['width']) && $item['width'] !== null ? round($width, 2) : null,
            'height' => isset($item['height']) && $item['height'] !== null ? round($height, 2) : null,
            'deductions' => round($deductions, 2),
            'gross_quantity' => round($gross, 2),
            'net_quantity' => round($net, 2),
            'notes' => $item['notes'] ?? null,
            'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : 0,
        ];
    }

    /**
     * Calculate summary aggregates for a collection or array of items.
     *
     * @param iterable $items
     * @return array
     */
    public function calculateSummary(iterable $items): array
    {
        $totalArea = 0.0;
        $totalVolume = 0.0;
        $totalLinear = 0.0;
        $totalCount = 0.0;

        foreach ($items as $item) {
            $itemArray = is_array($item) ? $item : $item->toArray();
            $type = $itemArray['measurement_type'] ?? self::resolveType($itemArray['unit'] ?? 'm2');
            $net = (float) ($itemArray['net_quantity'] ?? 0.0);

            switch ($type) {
                case 'volume':
                case 'm3':
                    $totalVolume += $net;
                    break;
                case 'linear':
                case 'lm':
                    $totalLinear += $net;
                    break;
                case 'count':
                case 'pcs':
                    $totalCount += $net;
                    break;
                case 'area':
                case 'm2':
                default:
                    $totalArea += $net;
                    break;
            }
        }

        return [
            'total_area' => round($totalArea, 2),
            'total_volume' => round($totalVolume, 2),
            'total_linear' => round($totalLinear, 2),
            'total_count' => round($totalCount, 2),
        ];
    }
}
