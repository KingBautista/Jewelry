<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;

    protected $table = 'email_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get email setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->where('is_active', true)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set email setting value by key
     */
    public static function setValue($key, $value, $description = null, $isActive = true)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description,
                'is_active' => $isActive
            ]
        );
    }

    /**
     * Get all email settings as key-value pairs
     */
    public static function getAllSettings()
    {
        return static::where('is_active', true)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get mail configuration for Laravel
     */
    public static function getMailConfig()
    {
        $settings = static::getAllSettings();
        
        return [
            'from' => [
                'address' => $settings['mail_from_address'] ?? env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => $settings['mail_from_name'] ?? env('MAIL_FROM_NAME', 'Example'),
            ],
            'reply_to' => [
                'address' => $settings['mail_reply_to_address'] ?? null,
                'name' => $settings['mail_reply_to_name'] ?? null,
            ],
            'admin_email' => $settings['admin_email'] ?? null,
            'admin_name' => $settings['admin_name'] ?? null,
        ];
    }
}
