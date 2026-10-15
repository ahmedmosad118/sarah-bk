<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimation_id',
        'boq_item_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'material_cost',
        'labor_cost',
        'equipment_cost',
        'subcontractor_cost',
        'other_cost',
        'unit_cost',
        'total_cost',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'equipment_cost' => 'decimal:2',
        'subcontractor_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Estimation document.
     */
    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class);
    }

    /**
     * Source BOQ quantity line this item costs.
     */
    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    /**
     * Cost component breakdowns for this line (materials, labor, equipment, subcontractor, other).
     */
    public function costs(): HasMany
    {
        return $this->hasMany(EstimationItemCost::class);
    }
}
