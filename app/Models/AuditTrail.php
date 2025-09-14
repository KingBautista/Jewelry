<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'module',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'model_type',
        'model_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $appends = [
        'formatted_created_at',
        'action_badge_class',
    ];

    /**
     * Get the user that owns the audit trail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get formatted created at attribute.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    /**
     * Get action badge class attribute.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match(strtoupper($this->action)) {
            'CREATE' => 'text-bg-success',
            'UPDATE' => 'text-bg-warning',
            'DELETE' => 'text-bg-danger',
            'RESTORE' => 'text-bg-info',
            'LOGIN' => 'text-bg-success',
            'LOGOUT' => 'text-bg-secondary',
            default => 'text-bg-secondary',
        };
    }

    /**
     * Scope a query to filter by module.
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by model.
     */
    public function scopeByModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Get available modules.
     */
    public static function getModules()
    {
        return static::distinct()
            ->pluck('module')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get available actions.
     */
    public static function getActions()
    {
        return static::distinct()
            ->pluck('action')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Create audit trail entry.
     */
    public static function createEntry(array $data)
    {
        return static::create([
            'user_id' => $data['user_id'] ?? auth()->id(),
            'user_name' => $data['user_name'] ?? auth()->user()?->user_email ?? 'System',
            'module' => $data['module'],
            'action' => $data['action'],
            'description' => $data['description'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'model_type' => $data['model_type'] ?? null,
            'model_id' => $data['model_id'] ?? null,
        ]);
    }
}