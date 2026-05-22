<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAudit('created', []);
        });

        static::updated(function ($model) {
            $model->recordAudit('updated', $model->getOriginal());
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->getOriginal());
        });
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected function recordAudit(string $event, array $oldValues = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $this->getAttributes(),
            'notes' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
