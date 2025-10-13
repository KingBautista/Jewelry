# GoDaddy Email Configuration Guide

## ✅ WORKING CONFIGURATION (TESTED & VERIFIED)

**Test Date**: January 13, 2025  
**Test Email**: bautistael23@gmail.com  
**Status**: ✅ WORKING - Emails sent successfully

### For Production Server (.env file)

Create or update your `.env` file with these **VERIFIED WORKING** settings:

```env
# ✅ WORKING Mail Configuration for GoDaddy
MAIL_MAILER=smtp
MAIL_HOST=smtpout.secureserver.net
MAIL_PORT=587
MAIL_USERNAME=admin@illussso.com
MAIL_PASSWORD=L@.K1BPIntNbGqcut3
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@illussso.com
MAIL_FROM_NAME="${APP_NAME}"

# Application Settings
APP_NAME="Jewelry Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.illussso.com

# Admin Panel URLs
ADMIN_APP_URL=https://admin.illussso.com
CUSTOMER_APP_URL=https://customer.illussso.com

# Security
PEPPER_HASH=your-pepper-hash-here
```

### ✅ Test Results Summary

1. **SMTP Connection**: ✅ SUCCESS
   - Host: smtpout.secureserver.net:587
   - Server Response: 220 sxb1plsmtpa01-03.prod.sxb1.secureserver.net ready

2. **Email Sending**: ✅ SUCCESS
   - Simple emails: ✅ Working
   - Forgot password emails: ✅ Working
   - All email templates: ✅ Working

3. **PHP Configuration**: ✅ READY
   - PHP Version: 8.3.14
   - OpenSSL: Enabled
   - allow_url_fopen: Enabled

## Alternative GoDaddy SMTP Settings

**Note**: The primary configuration above is working. These alternatives are provided for reference only.

### Option 1: SSL Configuration (Port 465)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtpout.secureserver.net
MAIL_PORT=465
MAIL_USERNAME=admin@illussso.com
MAIL_PASSWORD=L@.K1BPIntNbGqcut3
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=admin@illussso.com
MAIL_FROM_NAME="${APP_NAME}"
```
**Status**: ✅ Connection successful (tested)

### Option 2: Relay Server (NOT WORKING)
```env
MAIL_MAILER=smtp
MAIL_HOST=relay-hosting.secureserver.net
MAIL_PORT=25
MAIL_USERNAME=admin@illussso.com
MAIL_PASSWORD="L@.k$BP*ut9b#Gq"
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=admin@illussso.com
MAIL_FROM_NAME="${APP_NAME}"
```
**Status**: ❌ Connection failed (tested)

### Option 3: Localhost (NOT WORKING)
```env
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=admin@illussso.com
MAIL_FROM_NAME="${APP_NAME}"
```
**Status**: ❌ Connection failed (tested)

## Important Notes

1. **Domain Authentication**: Make sure your domain `illussso.com` has proper SPF, DKIM, and DMARC records set up in GoDaddy DNS.

2. **Email Account**: Ensure `admin@illussso.com` is a valid email account in your GoDaddy hosting.

3. **Firewall**: Check if GoDaddy has any firewall restrictions on SMTP ports.

4. **PHP Configuration**: Make sure PHP's `allow_url_fopen` and `openssl` extensions are enabled.

## DNS Records to Add

Add these DNS records in your GoDaddy DNS management:

### SPF Record
```
Type: TXT
Name: @
Value: v=spf1 include:secureserver.net ~all
```

### DKIM Record
```
Type: TXT
Name: default._domainkey
Value: (Get this from GoDaddy email settings)
```

### DMARC Record
```
Type: TXT
Name: _dmarc
Value: v=DMARC1; p=quarantine; rua=mailto:admin@illussso.com
```

## Testing Commands

After updating your .env file, run these commands:

```bash
# Clear configuration cache
php artisan config:clear
php artisan cache:clear

# Test email sending (VERIFIED WORKING)
php artisan tinker
>>> Mail::raw('Test email', function($message) { $message->to('bautistael23@gmail.com')->subject('Test'); });

# Or use the test scripts (VERIFIED WORKING)
php test-godaddy-email.php
php test-forgot-password-endpoint.php bautistael23@gmail.com
php test-simple-email-final.php
```

## ✅ VERIFIED WORKING FEATURES

1. **Basic Email Sending**: ✅ Working
2. **Forgot Password Emails**: ✅ Working  
3. **User Welcome Emails**: ✅ Working
4. **Password Update Emails**: ✅ Working
5. **Email Verification**: ✅ Working
6. **All Email Templates**: ✅ Working with modern design

## Troubleshooting

1. **Check GoDaddy Email Settings**: Log into your GoDaddy account and verify the email account exists and is active.

2. **Check PHP Error Logs**: Look for SMTP connection errors in your server's error logs.

3. **Test SMTP Connection**: Use a tool like `telnet smtpout.secureserver.net 587` to test connectivity.

4. **Contact GoDaddy Support**: If none of the above work, contact GoDaddy support for your specific hosting plan's SMTP settings.
