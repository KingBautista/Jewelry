<?php

namespace App\Traits;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;

trait AuditTrailTrait
{
    /**
     * Boot the trait and set up model event listeners.
     */
    protected static function bootAuditTrailTrait()
    {
        static::created(function ($model) {
            static::createAuditTrail($model, 'CREATE');
        });

        static::updated(function ($model) {
            static::createAuditTrail($model, 'UPDATE', $model->getOriginal());
        });

        static::deleted(function ($model) {
            static::createAuditTrail($model, 'DELETE');
        });

        static::restored(function ($model) {
            static::createAuditTrail($model, 'RESTORE');
        });
    }

    /**
     * Create audit trail entry.
     */
    protected static function createAuditTrail($model, $action, $oldValues = null)
    {
        $module = static::getAuditModuleName();
        $description = static::getAuditDescription($model, $action);
        
        $data = [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->user_email ?? 'System',
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
        ];

        if ($action === 'UPDATE' && $oldValues) {
            $data['old_values'] = $oldValues;
            $data['new_values'] = $model->getAttributes();
        } elseif ($action === 'CREATE') {
            $data['new_values'] = $model->getAttributes();
        } elseif (in_array($action, ['DELETE', 'RESTORE'])) {
            $data['old_values'] = $model->getAttributes();
        }

        AuditTrail::create($data);
    }

    /**
     * Get the module name for audit trail.
     * Override this method in your model to customize the module name.
     */
    protected static function getAuditModuleName()
    {
        $className = class_basename(static::class);
        return str_replace('_', ' ', snake_case($className));
    }

    /**
     * Get the description for audit trail.
     * Override this method in your model to customize the description.
     */
    protected static function getAuditDescription($model, $action)
    {
        $module = static::getAuditModuleName();
        $modelName = class_basename(static::class);
        
        return match($action) {
            'CREATE' => "Created new {$module} record",
            'UPDATE' => "Updated {$module} record",
            'DELETE' => "Deleted {$module} record",
            'RESTORE' => "Restored {$module} record",
            default => "{$action} action performed on {$module}",
        };
    }

    /**
     * Manually create audit trail entry.
     */
    public function createAuditEntry($action, $description = null, $oldValues = null, $newValues = null)
    {
        $module = static::getAuditModuleName();
        
        $data = [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->user_email ?? 'System',
            'module' => $module,
            'action' => $action,
            'description' => $description ?? static::getAuditDescription($this, $action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ];

        return AuditTrail::create($data);
    }

    /**
     * Get audit trail entries for this model.
     */
    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class, 'model_id')
            ->where('model_type', get_class($this))
            ->orderBy('created_at', 'desc');
    }
}
