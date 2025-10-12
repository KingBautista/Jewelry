# CORS Troubleshooting Guide

## Understanding the CORS Error

The error you're experiencing:
```
Access to XMLHttpRequest at 'https://api.illussso.com/api/customer-management/customers' 
from origin 'https://admin.illussso.com' has been blocked by CORS policy: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

This means:
1. Your frontend at `https://admin.illussso.com` is trying to make API calls to `https://api.illussso.com`
2. The API server is not sending the proper CORS headers
3. The browser blocks the request for security reasons

## Solutions Implemented

### 1. Updated CORS Configuration (`config/cors.php`)
- Added explicit allowed origins including your production domains
- Configured proper headers and methods
- Set appropriate cache settings

### 2. Created Custom CORS Middleware (`CustomCorsMiddleware.php`)
- Handles preflight OPTIONS requests
- Adds CORS headers to all responses
- Provides fallback for development environments

### 3. Updated Middleware Stack (`app/Http/Kernel.php`)
- Added custom CORS middleware before Laravel's default CORS middleware
- Ensures CORS headers are set early in the request lifecycle

## Testing CORS

### Test Script
A test script has been created at `/test-cors.php` that you can use to verify CORS is working:

```bash
# Test from your admin panel or use curl:
curl -H "Origin: https://admin.illussso.com" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS \
     https://api.illussso.com/test-cors.php
```

### Browser Developer Tools
1. Open browser developer tools (F12)
2. Go to Network tab
3. Make a request from your admin panel
4. Check the response headers for CORS headers:
   - `Access-Control-Allow-Origin`
   - `Access-Control-Allow-Methods`
   - `Access-Control-Allow-Headers`

## Additional Troubleshooting Steps

### 1. Check Server Configuration
If you're using a web server (Apache/Nginx), ensure it's not interfering with CORS headers:

**Apache (.htaccess):**
```apache
Header always set Access-Control-Allow-Origin "https://admin.illussso.com"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Accept, Authorization, Content-Type, X-Requested-With"
```

**Nginx:**
```nginx
add_header 'Access-Control-Allow-Origin' 'https://admin.illussso.com' always;
add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
add_header 'Access-Control-Allow-Headers' 'Accept, Authorization, Content-Type, X-Requested-With' always;
```

### 2. Check Laravel Routes
Ensure your API routes are properly defined and accessible:

```bash
php artisan route:list --path=api
```

### 3. Check Middleware Order
The middleware order in `Kernel.php` is important. CORS middleware should be early in the stack.

### 4. Environment-Specific Issues
- Check if you have different configurations for different environments
- Ensure the production server has the updated configuration
- Clear all caches: `php artisan config:clear && php artisan route:clear && php artisan cache:clear`

### 5. SSL/HTTPS Issues
If you're using HTTPS, ensure:
- SSL certificates are valid
- Mixed content issues are resolved
- Both domains are properly configured

## Common CORS Headers

The following headers should be present in API responses:

```
Access-Control-Allow-Origin: https://admin.illussso.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Accept, Authorization, Content-Type, X-Requested-With, X-CSRF-TOKEN
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 86400
```

## Debugging Commands

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Check current CORS configuration
php artisan config:show cors

# Test API endpoint
curl -I https://api.illussso.com/api/customer-management/customers

# Check if middleware is loaded
php artisan route:list --path=api
```

## Production Deployment Checklist

1. ✅ Update `config/cors.php` with production domains
2. ✅ Deploy `CustomCorsMiddleware.php`
3. ✅ Update `app/Http/Kernel.php` middleware stack
4. ✅ Clear all caches on production server
5. ✅ Test CORS with production domains
6. ✅ Verify web server configuration (if applicable)
7. ✅ Check SSL certificates
8. ✅ Test API endpoints from frontend

## If Issues Persist

1. Check server error logs
2. Verify the API server is actually running
3. Test with a simple curl request
4. Check if there are any proxy/CDN configurations interfering
5. Verify DNS resolution for both domains
6. Check firewall settings

## Contact Information

If you continue to experience issues, please provide:
- Server error logs
- Network request/response details from browser dev tools
- Current server configuration
- Any proxy/CDN configurations
