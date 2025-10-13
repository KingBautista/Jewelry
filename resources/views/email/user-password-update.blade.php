<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Updated - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            color: #155724;
        }
        .password-box {
            background: #f8f9fa;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .password {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #004085;
        }
        .account-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .account-details ul {
            margin: 0;
            padding-left: 20px;
        }
        .account-details li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Password Updated</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('app.name') }} Account Security</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->user_login }},</h2>
            
            <div class="success-box">
                <strong>🎉 Success!</strong> Your account password has been successfully updated.
            </div>
            
            <p>This email is to inform you that your password for {{ config('app.name') }} has been successfully updated by an administrator.</p>
            
            <div class="account-details">
                <p><strong>Account Information:</strong></p>
                <ul>
                    <li><strong>Username:</strong> {{ $user->user_login }}</li>
                    <li><strong>Email:</strong> {{ $user->user_email }}</li>
                    <li><strong>Updated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</li>
                </ul>
            </div>
            
            @if(isset($options['new_password']))
            <div class="password-box">
                <p style="margin: 0 0 10px 0; font-weight: 600;">Your new temporary password is:</p>
                <div class="password">{{ $options['new_password'] }}</div>
            </div>
            
            <div class="warning">
                <strong>🔒 Important Security Information:</strong><br>
                Please log in immediately and change this password for security purposes. Do not share this password with anyone.
            </div>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ $options['login_url'] ?? env('ADMIN_APP_URL') . '/login' }}" class="button">Login to System</a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not request this password change, please contact our support team immediately.
            </div>
            
            <div class="info-box">
                <strong>📧 Need Help?</strong><br>
                If you have any questions or concerns about this password update, please contact our support team immediately.
            </div>
            
            <p>Best regards,<br>
            <strong>{{ config('app.name') }} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This email was sent from the {{ config('app.name') }} system.</p>
            <p>Please do not reply to this email. For support, contact our customer service team.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
