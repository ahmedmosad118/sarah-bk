<?php

namespace App\Models;

use App\Core\Concerns\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Estimation extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia, HasApprovalWorkflow;

    protected function numberColumn(): string
    {
        return 'estimation_number';
    }

    protected $fillable = [
        'opportunity_id',
        'boq_id',
        'estimation_number',
        'version',
        'status',
        'title',
        'prepared_by',
        'prepared_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'notes',
        'direct_cost',
        'overhead_rate',
        'overhead_amount',
        'total_cost',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'prepared_at' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'direct_cost' => 'decimal:2',
        'overhead_rate' => 'decimal:2',
        'overhead_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Commercial Opportunity.
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Associated Approved BOQ revision (frozen historical source reference).
     */
    public function boq(): BelongsTo
    {
        return $this->belongsTo(Boq::class);
    }

    /**
     * Costed lines within this Estimation.
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Estimator / Cost engineer who prepared the study.
     */
    public function preparedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Technical/Engineering manager who reviewed the study.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Executive / GM who approved the study.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created the Estimation record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('commercial')
            ->logOnly([
                'estimation_number',
                'version',
                'status',
                'title',
                'direct_cost',
                'overhead_rate',
                'overhead_amount',
                'total_cost',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Media Library setup for attached calculation sheets / vendor quotes.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('public');
    }
}
