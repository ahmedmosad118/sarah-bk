<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Scope extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'opportunity_id',
        'measurement_id',
        'scope_number',
        'version',
        'status',
        'title',
        'general_inclusions',
        'general_exclusions',
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
     * Associated Approved Engineering Measurement revision.
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Technical engineer who prepared the scope of work.
     */
    public function preparedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Technical/Engineering manager who reviewed the scope.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Manager/Executive who approved the scope.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who created the scope record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Work package items within this scope.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ScopeItem::class)->orderBy('sort_order')->orderBy('id');
    }

    // Status Helpers
    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'Under Review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    public function isSuperseded(): bool
    {
        return $this->status === 'Superseded';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['Draft', 'Under Review'], true);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['Draft', 'Under Review'], true);
    }

    // Query Scopes
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForOpportunity(Builder $query, int $opportunityId): Builder
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    public function scopeForMeasurement(Builder $query, int $measurementId): Builder
    {
        return $query->where('measurement_id', $measurementId);
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
                'measurement_id',
                'scope_number',
                'version',
                'status',
                'title',
                'general_inclusions',
                'general_exclusions',
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
                'created' => __('activity.scope_created', ['number' => $this->scope_number]),
                'updated' => __('activity.scope_updated', ['number' => $this->scope_number]),
                'deleted' => __('activity.scope_deleted', ['number' => $this->scope_number]),
                default => __('activity.scope_event', ['event' => $eventName, 'number' => $this->scope_number]),
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
