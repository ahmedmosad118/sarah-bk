<?php

namespace App\Services;

use App\Models\EstimationItem;
use App\Models\Quotation;
use App\Models\QuotationItem;

class QuotationCalculationService
{
    /**
     * Forbidden internal costing keys that MUST NEVER be exposed to clients or in quotation outputs.
     */
    public const FORBIDDEN_COST_KEYS = [
        'direct_cost',
        'material_cost',
        'labor_cost',
        'equipment_cost',
        'subcontractor_cost',
        'other_cost',
        'unit_cost',
        'overhead_rate',
        'overhead_amount',
        'total_cost',
        'costs', // component breakdown array in estimation items
    ];

    /**
     * Calculate line selling price and total derived from source estimation item.
     *
     * @param EstimationItem $estimationItem Source costed item
     * @param float|null $itemMarkup Specific markup override for this line (null = use doc default)
     * @param float $docMarkup Default document markup rate
     * @return array
     */
    public function calculateLineSellingPrice(EstimationItem $estimationItem, ?float $itemMarkup, float $docMarkup): array
    {
        $effectiveMarkup = $itemMarkup !== null ? (float) $itemMarkup : (float) $docMarkup;
        $unitCost = (float) $estimationItem->unit_cost;
        $quantity = (float) $estimationItem->quantity;

        // Selling unit price = Cost + Markup
        $unitSellingPrice = round($unitCost * (1 + ($effectiveMarkup / 100)), 2);
        $lineTotal = round($quantity * $unitSellingPrice, 2);

        return [
            'markup_rate' => $itemMarkup !== null ? round($itemMarkup, 2) : null,
            'unit_selling_price' => $unitSellingPrice,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Recalculate quotation line items and document financial totals.
     */
    public function recalculateQuotationTotals(
        Quotation $quotation,
        ?float $defaultMarkupRate = null,
        ?string $discountType = null,
        ?float $discountRate = null,
        ?float $discountAmount = null,
        ?float $taxRate = null
    ): void {
        if ($defaultMarkupRate !== null) {
            $quotation->markup_rate = round($defaultMarkupRate, 2);
        }
        if ($discountType !== null) {
            $quotation->discount_type = in_array($discountType, ['percentage', 'fixed'], true) ? $discountType : 'percentage';
        }
        if ($discountRate !== null) {
            $quotation->discount_rate = round($discountRate, 2);
        }
        if ($discountAmount !== null) {
            $quotation->discount_amount = round($discountAmount, 2);
        }
        if ($taxRate !== null) {
            $quotation->tax_rate = round($taxRate, 2);
        }

        $docMarkup = (float) $quotation->markup_rate;

        // Ensure items and estimationItems are loaded
        $quotation->loadMissing(['items.estimationItem']);

        $subtotal = 0.00;

        foreach ($quotation->items as $item) {
            if ($item->estimationItem) {
                $calc = $this->calculateLineSellingPrice($item->estimationItem, $item->markup_rate, $docMarkup);

                $item->unit_selling_price = $calc['unit_selling_price'];
                $item->line_total = $calc['line_total'];
                $item->save();
            }

            $subtotal += (float) $item->line_total;
        }

        $subtotal = round($subtotal, 2);

        // Calculate discount
        $discountValue = 0.00;
        if ($quotation->discount_type === 'fixed') {
            $discountValue = min($subtotal, (float) $quotation->discount_amount);
        } else {
            $rate = (float) $quotation->discount_rate;
            $discountValue = round($subtotal * ($rate / 100), 2);
        }

        // Calculate tax and grand total
        $taxableAmount = max(0.00, round($subtotal - $discountValue, 2));
        $taxRate = (float) $quotation->tax_rate;
        $taxAmount = round($taxableAmount * ($taxRate / 100), 2);
        $grandTotal = round($taxableAmount + $taxAmount, 2);

        $quotation->subtotal = $subtotal;
        $quotation->discount_value = $discountValue;
        $quotation->discount_amount = $discountValue;
        $quotation->taxable_amount = $taxableAmount;
        $quotation->tax_amount = $taxAmount;
        $quotation->grand_total = $grandTotal;

        $quotation->save();
    }

    /**
     * Recursively scrubs any confidential internal costing field from data.
     */
    public function stripConfidentialCostData(mixed $data): mixed
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                if (in_array((string) $key, self::FORBIDDEN_COST_KEYS, true)) {
                    continue;
                }
                $clean[$key] = $this->stripConfidentialCostData($value);
            }
            return $clean;
        }

        if (is_object($data)) {
            $array = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
            return $this->stripConfidentialCostData($array);
        }

        return $data;
    }
}
