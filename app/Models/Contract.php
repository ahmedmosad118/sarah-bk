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

class Contract extends Model implements HasMedia
{
    use HasApprovalWorkflow, HasFactory, InteractsWithMedia, LogsActivity;

    protected $table = 'contracts';

    protected function numberColumn(): string
    {
        return 'contract_number';
    }

    protected $fillable = [
        'opportunity_id',
        'quotation_id',
        'parent_contract_id',
        'contract_number',
        'version',
        'status',
        'signature_status',
        'contract_value',
        'value_adjustment_reason',
        'start_date',
        'expected_completion_date',
        'terms_and_conditions',
        'notes',
        'prepared_by',
        'prepared_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'signed_by',
        'signed_at',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'contract_value' => 'decimal:2',
        'start_date' => 'date:Y-m-d',
        'expected_completion_date' => 'date:Y-m-d',
        'prepared_at' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'signed_at' => 'datetime',
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
     * Authoritative approved & accepted Quotation baseline.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Parent predecessor contract if this is a revision.
     */
    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    /**
     * Revisions derived from this contract.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_contract_id')->orderBy('version', 'desc');
    }

    /**
     * Payment milestones for this contract.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ContractPaymentMilestone::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * User who prepared the contract.
     */
    public function preparedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * User who reviewed the contract internally.
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Executive / Manager who approved the contract internally.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Authorized signatory who signed the contract.
     */
    public function signedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * User who created the contract record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Signature Status Helpers
    public function isNotSent(): bool
    {
        return $this->signature_status === 'Not Sent';
    }

    public function isSentForSignature(): bool
    {
        return $this->signature_status === 'Sent for Signature';
    }

    public function isSigned(): bool
    {
        return $this->signature_status === 'Signed';
    }

    public function isVoid(): bool
    {
        return $this->signature_status === 'Void';
    }

    /**
     * Contract may only be sent for signature after being approved internally.
     */
    public function canBeSentForSignature(): bool
    {
        return $this->isApproved() && $this->isNotSent();
    }

    /**
     * Signing is only permissible after contract is approved and has been sent for signature.
     */
    public function canBeSigned(): bool
    {
        return $this->isApproved() && ($this->isSentForSignature() || $this->isNotSent());
    }

    /**
     * Voiding is permissible before signing. A signed legal contract cannot be voided casually.
     */
    public function canBeVoided(): bool
    {
        return ! $this->isVoid() && ! $this->isSigned();
    }

    /**
     * Filter query by signature status.
     */
    public function scopeSignatureStatus(Builder $query, string $status): Builder
    {
        return $query->where('signature_status', $status);
    }

    /**
     * Activity log configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('commercial')
            ->logOnly([
                'contract_number',
                'version',
                'status',
                'signature_status',
                'contract_value',
                'value_adjustment_reason',
                'start_date',
                'expected_completion_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Media Library setup for signed contract PDFs, scanned attachments, etc.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('public');
    }
}
