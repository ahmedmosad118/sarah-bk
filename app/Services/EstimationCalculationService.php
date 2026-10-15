<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\EstimationItem;
use App\Models\EstimationItemCost;

class EstimationCalculationService
{
    /**
     * Supported direct cost component types.
     */
    public const COST_TYPES = [
        'material',
        'labor',
        'equipment',
        'subcontractor',
        'other',
    ];

    /**
     * Calculate cost component amount based on source BOQ quantity and unit rate.
     */
    public function calculateComponentAmount(float $quantity, float $costRate): float
    {
        return round($quantity * max(0, $costRate), 2);
    }

    /**
     * Recalculate and persist an EstimationItem and its child costs from input data.
     *
     * @param EstimationItem $item
     * @param array $costsData Array of ['cost_type' => ..., 'cost_rate' => ..., 'description' => ..., 'notes' => ...]
     * @return EstimationItem
     */
    public function syncItemCosts(EstimationItem $item, array $costsData): EstimationItem
    {
        // Remove existing child costs
        $item->costs()->delete();

        $subtotals = [
            'material' => 0.00,
            'labor' => 0.00,
            'equipment' => 0.00,
            'subcontractor' => 0.00,
            'other' => 0.00,
        ];

        $quantity = (float) $item->quantity;

        foreach ($costsData as $costInput) {
            $costType = strtolower((string) ($costInput['cost_type'] ?? 'other'));
            if (!in_array($costType, self::COST_TYPES, true)) {
                $costType = 'other';
            }

            $rate = max(0, (float) ($costInput['cost_rate'] ?? 0.00));
            $amount = $this->calculateComponentAmount($quantity, $rate);

            $item->costs()->create([
                'cost_type' => $costType,
                'description' => $costInput['description'] ?? null,
                'cost_rate' => $rate,
                'amount' => $amount,
                'notes' => $costInput['notes'] ?? null,
            ]);

            $subtotals[$costType] += $amount;
        }

        $lineTotalCost = array_sum($subtotals);
        $lineUnitCost = $quantity > 0 ? round($lineTotalCost / $quantity, 2) : 0.00;

        $item->update([
            'material_cost' => round($subtotals['material'], 2),
            'labor_cost' => round($subtotals['labor'], 2),
            'equipment_cost' => round($subtotals['equipment'], 2),
            'subcontractor_cost' => round($subtotals['subcontractor'], 2),
            'other_cost' => round($subtotals['other'], 2),
            'unit_cost' => $lineUnitCost,
            'total_cost' => round($lineTotalCost, 2),
        ]);

        return $item;
    }

    /**
     * Recalculate and persist whole Estimation document direct cost, overhead, and total.
     */
    public function recalculateEstimationTotals(Estimation $estimation, ?float $overheadRate = null): Estimation
    {
        // Re-read items to sum exact direct cost
        $directCost = (float) $estimation->items()->sum('total_cost');

        $rate = $overheadRate !== null ? max(0, min(100, $overheadRate)) : (float) $estimation->overhead_rate;
        $overheadAmount = round($directCost * ($rate / 100), 2);
        $totalCost = round($directCost + $overheadAmount, 2);

        $estimation->update([
            'direct_cost' => round($directCost, 2),
            'overhead_rate' => round($rate, 2),
            'overhead_amount' => $overheadAmount,
            'total_cost' => $totalCost,
        ]);

        return $estimation;
    }
}
