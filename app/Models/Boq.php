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

class Boq extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia, HasApprovalWorkflow;

    protected function numberColumn(): string
    {
        return 'boq_number';
    }

    protected $fillable = [
        'opportunity_id',
        'scope_id',
        'boq_number',
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
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'prepared_at' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
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
     * Associated Approved Scope of Work revision (frozen historical reference).
     */
    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }

    /**
     * Technical engineer who prepared the BOQ.
     */
    public function preparedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Technical/Engineering manager who reviewed the BOQ.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Manager/Executive who approved the BOQ.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created the BOQ record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pricable quantity lines within this BOQ.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BoqItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Cost estimations based on this BOQ.
     */
    public function estimations(): HasMany
    {
        return $this->hasMany(Estimation::class);
    }

    // Query Scopes

    public function scopeForOpportunity(Builder $query, int $opportunityId): Builder
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    public function scopeForScope(Builder $query, int $scopeId): Builder
    {
        return $query->where('scope_id', $scopeId);
    }

    /**
     * Configure Spatie Activity Logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('commercial')
            ->logOnly([
                'opportunity_id',
                'scope_id',
                'boq_number',
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
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.boq_created', ['number' => $this->boq_number]),
                'updated' => __('activity.boq_updated', ['number' => $this->boq_number]),
                'deleted' => __('activity.boq_deleted', ['number' => $this->boq_number]),
                default => __('activity.boq_event', ['event' => $eventName, 'number' => $this->boq_number]),
            });
    }

    /**
     * Register Spatie Media Library Collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }
}
