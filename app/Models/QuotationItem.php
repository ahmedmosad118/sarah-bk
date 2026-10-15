<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'estimation_item_id',
        'item_code',
        'description',
        'unit',
        'quantity',
        'markup_rate',
        'unit_selling_price',
        'line_total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'markup_rate' => 'decimal:2',
        'unit_selling_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Quotation document.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Source estimation item from which this line was costed and priced.
     */
    public function estimationItem(): BelongsTo
    {
        return $this->belongsTo(EstimationItem::class);
    }
}
