<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - Customer Portal</title>
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
            background: linear-gradient(135deg, #D4AF37 0%, #F7E7CE 50%, #FFF8DC 100%);
            padding: 30px;
            text-align: center;
            color: #2C2C2C;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .password-box {
            background: #f8f9fa;
            border: 2px solid #D4AF37;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .password {
            font-size: 24px;
            font-weight: bold;
            color: #D4AF37;
            font-family: monospace;
            letter-spacing: 2px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37 0%, #F7E7CE 50%, #FFF8DC 100%);
            color: #2C2C2C;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Customer Portal Access</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->user_login }},</h2>
            
            <p>We received a request to reset your password for the Customer Portal. Your account has been updated with a new temporary password.</p>
            
            <div class="password-box">
                <p style="margin: 0 0 10px 0; font-weight: 600;">Your new temporary password is:</p>
                <div class="password">{{ $newPassword }}</div>
            </div>
            
            <p><strong>Important Security Information:</strong></p>
            <ul>
                <li>Please log in immediately and change this password</li>
                <li>Keep your login credentials secure</li>
                <li>Do not share this password with anyone</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Login to Customer Portal</a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not request this password reset, please contact our support team immediately.
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>
            <strong>Jewelry Management Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This email was sent from the Jewelry Management System Customer Portal.</p>
            <p>Please do not reply to this email. For support, contact our customer service team.</p>
        </div>
    </div>
</body>
</html>
