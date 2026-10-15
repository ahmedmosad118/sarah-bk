<?php

namespace App\Services;

use App\Models\Scope;
use App\Models\ScopeItem;
use Illuminate\Validation\ValidationException;

class BoqGenerationService
{
    /**
     * Build BOQ lines from an approved scope.
     *
     * Dual-authority derivation:
     * - description/specification inherited from the ScopeItem
     * - unit + quantity derived strictly from MeasurementItems linked to that
     *   ScopeItem via the scope_item_measurement_item pivot
     *   (quantity = SUM of net_quantity, only when units are homogeneous).
     *
     * Scope items with mixed units or with no linked measurement items are
     * NOT generated; they are reported in 'skipped' for explicit user action.
     *
     * @return array{lines: array, skipped: array}
     */
    public function buildItemsFromScope(Scope $scope): array
    {
        $scope->loadMissing(['items.measurementItems']);

        $lines = [];
        $skipped = [];
        $groupSeq = [];
        $groupCounter = 0;
        $sortOrder = 0;

        foreach ($scope->items as $scopeItem) {
            // Defensive Lineage Guard: Only consume measurement items belonging to the scope's exact measurement revision
            $linked = $scopeItem->measurementItems->where('measurement_id', $scope->measurement_id);

            if ($linked->isEmpty()) {
                $skipped[] = [
                    'scope_item_id' => $scopeItem->id,
                    'item_name' => $scopeItem->item_name,
                    'trade_category' => $scopeItem->trade_category,
                    'reason' => 'no_linked_measurement_items',
                    'message' => __('messages.boq_scope_item_has_no_quantities', ['item' => $scopeItem->item_name]),
                ];

                continue;
            }

            $units = $linked->pluck('unit')->unique()->values()->all();

            if (count($units) !== 1) {
                $skipped[] = [
                    'scope_item_id' => $scopeItem->id,
                    'item_name' => $scopeItem->item_name,
                    'trade_category' => $scopeItem->trade_category,
                    'reason' => 'mixed_units',
                    'units' => $units,
                    'message' => __('messages.boq_scope_item_mixed_units', ['item' => $scopeItem->item_name]),
                ];

                continue;
            }

            $trade = (string) ($scopeItem->trade_category ?: 'عام');
            if (! isset($groupSeq[$trade])) {
                $groupCounter++;
                $groupSeq[$trade] = ['group' => $groupCounter, 'seq' => 0];
            }
            $groupSeq[$trade]['seq']++;

            $lines[] = [
                'scope_item_id' => $scopeItem->id,
                'item_code' => $groupSeq[$trade]['group'].'.'.$groupSeq[$trade]['seq'],
                'description' => $this->composeDescription($scopeItem->item_name, $scopeItem->specification),
                'unit' => $units[0],
                'quantity' => round((float) $linked->sum('net_quantity'), 2),
                'notes' => $scopeItem->notes,
                'sort_order' => $sortOrder++,
            ];
        }

        return ['lines' => $lines, 'skipped' => $skipped];
    }

    /**
     * Re-derive unit + quantity for a single scope item (server-side truth).
     * Client-supplied quantity/unit values are never trusted.
     *
     * @throws ValidationException When the scope item is foreign, unlinked, or mixed-unit.
     */
    public function deriveLineForScopeItem(int $scopeId, int $scopeItemId): array
    {
        $scopeItem = ScopeItem::with('measurementItems')->findOrFail($scopeItemId);

        if ((int) $scopeItem->scope_id !== (int) $scopeId) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_mismatch')],
            ]);
        }

        $scope = Scope::findOrFail($scopeId);
        // Defensive Lineage Guard: Only consume measurement items belonging to the scope's exact measurement revision
        $linked = $scopeItem->measurementItems->where('measurement_id', $scope->measurement_id);

        if ($linked->isEmpty()) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_has_no_quantities', ['item' => $scopeItem->item_name])],
            ]);
        }

        $units = $linked->pluck('unit')->unique()->values()->all();

        if (count($units) !== 1) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_mixed_units', ['item' => $scopeItem->item_name])],
            ]);
        }

        return [
            'description' => $this->composeDescription($scopeItem->item_name, $scopeItem->specification),
            'unit' => $units[0],
            'quantity' => round((float) $linked->sum('net_quantity'), 2),
        ];
    }

    /**
     * Compose the final BOQ line description from scope work title + specification.
     */
    public function composeDescription(?string $itemName, ?string $specification): string
    {
        $name = trim((string) ($itemName ?? ''));
        $spec = trim((string) ($specification ?? ''));

        if ($name !== '' && $spec !== '') {
            return $name."\n".$spec;
        }

        return $name !== '' ? $name : $spec;
    }
}
