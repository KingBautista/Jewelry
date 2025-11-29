<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 50%, #004085 100%);
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
        .welcome-box {
            background: #d1ecf1;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            color: #0c5460;
        }
        .password-box {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .password {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 50%, #004085 100%);
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
        .features {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .features ul {
            margin: 0;
            padding-left: 20px;
        }
        .features li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to {{ config('app.name') }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Your account has been created successfully</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->user_login }},</h2>
            
            <div class="welcome-box">
                <strong>🎊 Congratulations!</strong> Your account has been successfully created and you can now access the {{ config('app.name') }} system.
            </div>
            
            <p>Welcome to {{ config('app.name') }}! We're excited to have you on board. Your account has been set up and is ready to use.</p>
            
            <div class="account-details">
                <p><strong>Account Details:</strong></p>
                <ul>
                    <li><strong>Username:</strong> {{ $user->user_login }}</li>
                    <li><strong>Email:</strong> {{ $user->user_email }}</li>
                    <li><strong>Role:</strong> {{ $user->userRole->name ?? 'User' }}</li>
                    <li><strong>Account Created:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</li>
                </ul>
            </div>
            
            @if(isset($options['password']))
            <div class="password-box">
                <p style="margin: 0 0 10px 0; font-weight: 600;">Your temporary password is:</p>
                <div class="password">{{ $options['password'] }}</div>
            </div>
            
            <div class="warning">
                <strong>🔒 Important Security Information:</strong><br>
                Please log in immediately and change your temporary password for security purposes. Do not share this password with anyone.
            </div>
            @endif
            
            <div class="features">
                <p><strong>🚀 What you can do with your account:</strong></p>
                <ul>
                    <li>Access the {{ config('app.name') }} system</li>
                    <li>Manage your profile and settings</li>
                    <li>View and update your information</li>
                    <li>Access all features based on your role</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="https://customer.illussso.com/login" class="button">Login to System</a>
            </div>
            
            <div class="info-box">
                <strong>📧 Need Help?</strong><br>
                If you have any questions or need assistance getting started, please don't hesitate to contact our support team. We're here to help!
            </div>
            
            <p>We're thrilled to have you as part of the {{ config('app.name') }} community!</p>
            
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
