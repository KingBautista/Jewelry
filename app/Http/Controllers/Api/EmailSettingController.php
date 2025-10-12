<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EmailSettingController extends Controller
{
    /**
     * Get all email settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = EmailSetting::orderBy('key')->get();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific email setting by key
     */
    public function show(string $key): JsonResponse
    {
        try {
            $setting = EmailSetting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an email setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required',
                'description' => 'nullable|string',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $setting = EmailSetting::where('key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email setting not found'
                ], 404);
            }

            $setting->update($request->only(['value', 'description', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Email setting updated successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all email settings for configuration
     */
    public function getEmailSettings(): JsonResponse
    {
        try {
            $settings = [
                'mail_from_address' => EmailSetting::getValue('mail_from_address', env('MAIL_FROM_ADDRESS', '')),
                'mail_from_name' => EmailSetting::getValue('mail_from_name', env('MAIL_FROM_NAME', '')),
                'mail_reply_to_address' => EmailSetting::getValue('mail_reply_to_address', ''),
                'mail_reply_to_name' => EmailSetting::getValue('mail_reply_to_name', ''),
                'admin_email' => EmailSetting::getValue('admin_email', ''),
                'admin_name' => EmailSetting::getValue('admin_name', ''),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mail_from_address' => 'required|email',
                'mail_from_name' => 'required|string|max:255',
                'mail_reply_to_address' => 'nullable|email',
                'mail_reply_to_name' => 'nullable|string|max:255',
                'admin_email' => 'required|email',
                'admin_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update email settings
            EmailSetting::setValue('mail_from_address', $request->mail_from_address, 'Primary email address for outgoing emails');
            EmailSetting::setValue('mail_from_name', $request->mail_from_name, 'Display name for outgoing emails');
            EmailSetting::setValue('mail_reply_to_address', $request->mail_reply_to_address, 'Reply-to email address (optional)');
            EmailSetting::setValue('mail_reply_to_name', $request->mail_reply_to_name, 'Reply-to display name (optional)');
            EmailSetting::setValue('admin_email', $request->admin_email, 'Admin email address for notifications');
            EmailSetting::setValue('admin_name', $request->admin_name, 'Admin display name for notifications');

            return response()->json([
                'success' => true,
                'message' => 'Email settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get mail configuration for Laravel
     */
    public function getMailConfig(): JsonResponse
    {
        try {
            $config = EmailSetting::getMailConfig();
            
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch mail configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
