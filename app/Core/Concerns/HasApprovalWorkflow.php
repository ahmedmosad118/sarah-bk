<?php

namespace App\Core\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasApprovalWorkflow
{
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

    /**
     * Generates next revision number following "{base}-V{n}" convention.
     */
    public function nextRevisionNumber(): string
    {
        $currentNumber = (string) $this->{$this->numberColumn()};
        $base = preg_replace('/-V\d+$/i', '', $currentNumber);
        $nextVersion = ((int) $this->version) + 1;
        return "{$base}-V{$nextVersion}";
    }

    /**
     * Name of the reference number column (e.g. 'measurement_number' or 'scope_number').
     */
    abstract protected function numberColumn(): string;

    /**
     * Filter query by specific status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter query by Approved status.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'Approved');
    }
}
