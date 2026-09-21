<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JobTitle extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'job_title_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'name_ar', 'name_en', 'code', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء المسمى الوظيفي ' . ($this->name_ar ?: $this->name),
                'updated' => 'تم تحديث المسمى الوظيفي ' . ($this->name_ar ?: $this->name),
                'deleted' => 'تم حذف المسمى الوظيفي ' . ($this->name_ar ?: $this->name),
                default => "إجراء {$eventName} على المسمى الوظيفي " . ($this->name_ar ?: $this->name),
            });
    }
}
