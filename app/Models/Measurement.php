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

class Measurement extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia, HasApprovalWorkflow;

    protected function numberColumn(): string
    {
        return 'measurement_number';
    }

    protected $fillable = [
        'opportunity_id',
        'site_visit_id',
        'measurement_number',
        'version',
        'status',
        'measured_by',
        'measured_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'total_area',
        'total_volume',
        'total_linear',
        'total_count',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'measured_at' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_area' => 'decimal:2',
        'total_volume' => 'decimal:2',
        'total_linear' => 'decimal:2',
        'total_count' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Parent Commercial Opportunity for this measurement.
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Optional originating Site Visit (nullable).
     */
    public function siteVisit(): BelongsTo
    {
        return $this->belongsTo(SiteVisit::class);
    }

    /**
     * Engineer responsible for taking measurements.
     */
    public function measuredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    /**
     * Engineer/Manager who reviewed the measurement.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Engineer/Manager who approved the measurement.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created the measurement record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Line items for this measurement.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MeasurementItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scopes of work built on this measurement revision.
     */
    public function scopes(): HasMany
    {
        return $this->hasMany(Scope::class);
    }

    // Query Scopes

    public function scopeForOpportunity(Builder $query, int $opportunityId): Builder
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    public function scopeForSiteVisit(Builder $query, int $siteVisitId): Builder
    {
        return $query->where('site_visit_id', $siteVisitId);
    }

    public function scopeMeasuredBy(Builder $query, int $userId): Builder
    {
        return $query->where('measured_by', $userId);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'Approved');
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
                'site_visit_id',
                'measurement_number',
                'version',
                'status',
                'measured_by',
                'measured_at',
                'reviewed_by',
                'reviewed_at',
                'approved_by',
                'approved_at',
                'total_area',
                'total_volume',
                'total_linear',
                'total_count',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => __('activity.measurement_created', ['number' => $this->measurement_number]),
                'updated' => __('activity.measurement_updated', ['number' => $this->measurement_number]),
                'deleted' => __('activity.measurement_deleted', ['number' => $this->measurement_number]),
                default => __('activity.measurement_event', ['event' => $eventName, 'number' => $this->measurement_number]),
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
