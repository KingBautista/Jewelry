# Email Configuration for Testing

To test the email functionality, you need to configure your email settings in the `.env` file.

## Gmail SMTP Configuration (Recommended for Testing)

Add these lines to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Jewelry Business"
```

## Gmail App Password Setup

1. Enable 2-Factor Authentication on your Gmail account
2. Go to Google Account settings
3. Security → 2-Step Verification → App passwords
4. Generate an app password for "Mail"
5. Use this app password in MAIL_PASSWORD (not your regular Gmail password)

## Alternative: Use Log Driver for Testing

If you don't want to configure SMTP, you can use the log driver for testing:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=test@jewelry.com
MAIL_FROM_NAME="Jewelry Business"
```

This will log emails to `storage/logs/laravel.log` instead of sending them.

## Testing Email

1. Configure your email settings
2. Create an invoice in the system
3. Click the "Email" button in the invoice list
4. Check your email (bautistael23@gmail.com) for the invoice PDF attachment

## Troubleshooting

- Check `storage/logs/laravel.log` for email errors
- Ensure your email credentials are correct
- For Gmail, use app passwords, not regular passwords
- Check firewall settings if emails are not being sent
