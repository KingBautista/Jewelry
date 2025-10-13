<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 50%, #4c2a85 100%);
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
        .verification-box {
            background: #e2e3f0;
            border: 2px solid #6f42c1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            color: #4c2a85;
        }
        .password-box {
            background: #f8f9fa;
            border: 2px solid #6f42c1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .password {
            font-size: 24px;
            font-weight: bold;
            color: #6f42c1;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 50%, #4c2a85 100%);
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
        .steps {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Verify Your Email</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('app.name') }} Account Verification</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->user_login }},</h2>
            
            <div class="verification-box">
                <strong>🔐 Email Verification Required</strong><br>
                You registered an account on {{ config('app.name') }}. Before being able to use your account, you need to verify that this is your email address.
            </div>
            
            <p>Thank you for registering with {{ config('app.name') }}! To complete your account setup and ensure the security of your account, please verify your email address by clicking the button below.</p>
            
            <div class="account-details">
                <p><strong>Account Information:</strong></p>
                <ul>
                    <li><strong>Username:</strong> {{ $user->user_login }}</li>
                    <li><strong>Email:</strong> {{ $user->user_email }}</li>
                    <li><strong>Registration Date:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</li>
                </ul>
            </div>
            
            @if(isset($options['password']))
            <div class="password-box">
                <p style="margin: 0 0 10px 0; font-weight: 600;">Your temporary password is:</p>
                <div class="password">{{ $options['password'] }}</div>
            </div>
            
            <div class="warning">
                <strong>🔒 Important Security Information:</strong><br>
                Please log in immediately after verification and change your temporary password for security purposes. Do not share this password with anyone.
            </div>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ $options['verify_url'] }}" class="button">Verify Email Address</a>
            </div>
            
            <div class="steps">
                <p><strong>📋 Next Steps:</strong></p>
                <ol>
                    <li>Click the "Verify Email Address" button above</li>
                    <li>You will be redirected to confirm your email</li>
                    <li>Log in to your account using your credentials</li>
                    @if(isset($options['password']))
                    <li>Change your temporary password immediately</li>
                    @endif
                    <li>Start using your {{ config('app.name') }} account</li>
                </ol>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not register for this account, please ignore this email or contact our support team immediately.
            </div>
            
            <div class="info-box">
                <strong>📧 Need Help?</strong><br>
                If you have any questions about email verification or need assistance, please don't hesitate to contact our support team. We're here to help!
            </div>
            
            <p>Thank you for choosing {{ config('app.name') }}!</p>
            
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