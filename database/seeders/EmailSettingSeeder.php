<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailSetting;

class EmailSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default email settings (replacing .env values)
        EmailSetting::setValue(
            'mail_from_address', 
            'admin@example.com', 
            'Primary email address for outgoing emails (replaces MAIL_FROM_ADDRESS)'
        );

        EmailSetting::setValue(
            'mail_from_name', 
            'Jewelry Management System', 
            'Display name for outgoing emails (replaces MAIL_FROM_NAME)'
        );

        EmailSetting::setValue(
            'mail_reply_to_address', 
            '', 
            'Reply-to email address for email communications (optional)'
        );

        EmailSetting::setValue(
            'mail_reply_to_name', 
            '', 
            'Reply-to display name for email communications (optional)'
        );

        EmailSetting::setValue(
            'admin_email', 
            'admin@example.com', 
            'Admin email address for system notifications and communications'
        );

        EmailSetting::setValue(
            'admin_name', 
            'System Administrator', 
            'Admin display name that appears in email communications'
        );
    }
}
