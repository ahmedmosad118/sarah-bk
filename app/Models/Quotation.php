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

class Quotation extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia, HasApprovalWorkflow;

    protected function numberColumn(): string
    {
        return 'quotation_number';
    }

    protected $fillable = [
        'opportunity_id',
        'estimation_id',
        'quotation_number',
        'version',
        'status',
        'client_status',
        'title',
        'markup_rate',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'discount_value',
        'tax_rate',
        'subtotal',
        'taxable_amount',
        'tax_amount',
        'grand_total',
        'valid_until',
        'payment_terms_text',
        'notes',
        'client_notes',
        'prepared_by',
        'prepared_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'sent_by',
        'sent_at',
        'responded_at',
        'client_response_notes',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'markup_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'valid_until' => 'date:Y-m-d',
        'prepared_at' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
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
     * Authoritative approved Estimation baseline.
     */
    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class);
    }

    /**
     * Selling price items for this Quotation.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * User who prepared the Quotation.
     */
    public function preparedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * User who reviewed the Quotation internally.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Executive / Manager who approved the Quotation internally.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Sales representative / user who officially dispatched the offer to the client.
     */
    public function sentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * User who created the Quotation record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Client Status Helpers
    public function isNotSent(): bool
    {
        return $this->client_status === 'Not Sent';
    }

    public function isSent(): bool
    {
        return $this->client_status === 'Sent';
    }

    public function isAccepted(): bool
    {
        return $this->client_status === 'Accepted';
    }

    public function isRejected(): bool
    {
        return $this->client_status === 'Rejected';
    }

    public function isExpired(): bool
    {
        return $this->client_status === 'Expired';
    }

    /**
     * An offer may only be dispatched to the client after being approved internally.
     */
    public function canBeSent(): bool
    {
        return $this->isApproved() && $this->isNotSent();
    }

    /**
     * A client response may only be recorded after the offer has been marked as Sent.
     */
    public function canRecordResponse(): bool
    {
        return $this->isSent();
    }

    /**
     * Filter query by client status.
     */
    public function scopeClientStatus(Builder $query, string $status): Builder
    {
        return $query->where('client_status', $status);
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('commercial')
            ->logOnly([
                'quotation_number',
                'version',
                'status',
                'client_status',
                'title',
                'markup_rate',
                'discount_value',
                'tax_amount',
                'grand_total',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Media Library setup for client-facing signatures, PDF attachments, etc.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('public');
    }
}
