# Electroteca Security Guide

Complete guide to security implementation, testing, and maintenance for the Electroteca application.

---

## Table of Contents

1. [Security Features Overview](#security-features-overview)
2. [Implementation Details](#implementation-details)
3. [Configuration](#configuration)
4. [Testing Guide](#testing-guide)
5. [OWASP ZAP Testing](#owasp-zap-testing)
6. [Troubleshooting](#troubleshooting)
7. [Production Deployment](#production-deployment)
8. [Maintenance & Audits](#maintenance--audits)

---

## Security Features Overview

### ✅ Implemented Security Measures

1. **Security Headers Middleware**
   - Content Security Policy (CSP)
   - X-Frame-Options (Clickjacking protection)
   - X-Content-Type-Options (MIME sniffing protection)
   - X-XSS-Protection
   - Referrer-Policy
   - Permissions-Policy
   - Strict-Transport-Security (HSTS)

2. **HTTPS/SSL Enforcement**
   - Force HTTPS middleware
   - Trust proxies configuration
   - HSTS header support

3. **Two-Factor Authentication (2FA)**
   - TOTP-based 2FA (Google Authenticator, Authy)
   - Email-based OTP 2FA
   - Recovery codes
   - Currently optional (can be made mandatory)

4. **Microsoft Identity Model (Azure AD)**
   - Azure AD authentication configuration
   - Social login integration ready

5. **Custom JWT Authentication**
   - HTTP-Only cookies (XSS protection)
   - Access tokens (60 min expiration)
   - Refresh tokens (30 days expiration)
   - Automatic token refresh

6. **OWASP ZAP Integration**
   - Automated security scanning
   - Configuration files
   - Scan scripts

---

## Implementation Details

### 1. Security Headers Middleware

**File:** `app/Http/Middleware/SecurityHeadersMiddleware.php`

**What it does:**
- Adds security headers to all HTTP responses
- Prevents XSS, clickjacking, MIME sniffing attacks
- Configures CSP to allow Vite in development

**Key Headers:**
- `Content-Security-Policy`: Controls resource loading
- `X-Frame-Options: DENY`: Prevents clickjacking
- `X-Content-Type-Options: nosniff`: Prevents MIME sniffing
- `X-XSS-Protection: 1; mode=block`: XSS protection
- `Referrer-Policy`: Controls referrer information
- `Strict-Transport-Security`: Forces HTTPS (production)

**How it works:**
- Applied globally via `bootstrap/app.php`
- Different CSP rules for development vs production
- Development allows Vite dev server connections

### 2. HTTPS/SSL Enforcement

**Files:**
- `app/Http/Middleware/ForceHttpsMiddleware.php`
- `app/Http/Middleware/TrustProxies.php`
- `config/app.php`

**What it does:**
- Redirects all HTTP requests to HTTPS (when enabled)
- Handles HTTPS detection behind load balancers
- Sets HSTS header in production

**Configuration:**
```env
FORCE_HTTPS=true  # Enable in production
APP_URL=https://yourdomain.com
```

### 3. Two-Factor Authentication (2FA)

**Files:**
- `app/Http/Middleware/RequireTwoFactor.php`
- `app/Services/EmailTwoFactorService.php`
- `app/Http/Controllers/Auth/TwoFactorChallengeController.php`
- `database/migrations/*_add_email_2fa_to_users_table.php`

**What it does:**
- Provides TOTP-based 2FA (QR codes)
- Provides email-based OTP 2FA
- Generates recovery codes
- Currently optional (can be made mandatory)

**How it works:**
1. User enables 2FA in settings
2. QR code generated for TOTP apps
3. Email OTP sent automatically on login
4. User enters code from app or email
5. Recovery codes available as backup

**Database Fields:**
- `two_factor_secret`: Encrypted TOTP secret
- `two_factor_recovery_codes`: Encrypted recovery codes
- `two_factor_confirmed_at`: Confirmation timestamp
- `email_2fa_code`: Email OTP code
- `email_2fa_expires_at`: OTP expiration
- `email_2fa_verified_at`: Verification timestamp

### 4. Microsoft Identity Model (Azure AD)

**Files:**
- `config/services.php`
- `app/Http/Controllers/Auth/SocialAuthController.php`

**What it does:**
- Configures Azure AD authentication
- Integrates with existing social login system
- Ready for Microsoft/Azure AD credentials

**Configuration:**
```env
MICROSOFT_CLIENT_ID=your-client-id
MICROSOFT_CLIENT_SECRET=your-client-secret
MICROSOFT_TENANT_ID=your-tenant-id

# Or Azure AD specific:
AZURE_CLIENT_ID=your-azure-client-id
AZURE_CLIENT_SECRET=your-azure-client-secret
AZURE_TENANT_ID=your-azure-tenant-id
AZURE_AUTHORITY=https://login.microsoftonline.com
```

### 5. Custom JWT Authentication

**Files:**
- `app/Services/JwtService.php`
- `app/Http/Guards/JwtGuard.php`
- `app/Http/Middleware/JwtCookieMiddleware.php`
- `app/Http/Middleware/EnforceJwtHttpOnly.php`

**What it does:**
- Generates JWT access and refresh tokens
- Stores tokens in HTTP-Only cookies (not localStorage)
- Automatically refreshes expired tokens
- Prevents XSS attacks on tokens

**How it works:**
1. User logs in via Fortify (session-based)
2. `JwtCookieMiddleware` generates JWT tokens
3. Tokens stored as HTTP-Only cookies
4. On subsequent requests, tokens used for authentication
5. Refresh tokens automatically generate new access tokens

**Token Types:**
- **Access Token**: 60 minutes expiration
- **Refresh Token**: 30 days expiration

**Security Features:**
- HTTP-Only cookies (JavaScript cannot access)
- Secure flag (HTTPS only in production)
- SameSite=Strict (CSRF protection)
- Token revocation support

### 6. OWASP ZAP Integration

**Files:**
- `zap-config.xml`: ZAP configuration
- `zap-scan.sh`: Automated scan script
- `OWASP_ZAP_TESTING.md`: Testing documentation

**What it does:**
- Automated security vulnerability scanning
- Spider scanning (discovers all URLs)
- Active scanning (tests for vulnerabilities)
- Generates HTML, JSON, XML reports

---

## Configuration

### Environment Variables

Add to `.env`:

```env
# HTTPS Enforcement (Production)
FORCE_HTTPS=false  # Set to true in production
APP_URL=https://yourdomain.com  # Use https in production

# Microsoft Azure AD
MICROSOFT_CLIENT_ID=your-client-id
MICROSOFT_CLIENT_SECRET=your-client-secret
MICROSOFT_TENANT_ID=your-tenant-id

# Azure AD (Alternative)
AZURE_CLIENT_ID=your-azure-client-id
AZURE_CLIENT_SECRET=your-azure-client-secret
AZURE_TENANT_ID=your-azure-tenant-id
AZURE_AUTHORITY=https://login.microsoftonline.com

# Email Configuration (for 2FA OTP)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Database Migration

Run the migration for email 2FA:

```bash
php artisan migrate
```

This adds:
- `email_2fa_code` column
- `email_2fa_expires_at` column
- `email_2fa_verified_at` column

### Making 2FA Mandatory

To make 2FA mandatory again, edit `app/Http/Middleware/RequireTwoFactor.php` and uncomment the enforcement code.

---

## Testing Guide

### Quick Automated Test

Run the automated test script:

```bash
./test-security.sh
```

This tests:
- Application availability
- Security headers presence
- HTTPS enforcement
- CSRF protection
- Session cookie security
- File permissions

### Manual Testing Steps

#### 1. Security Headers Testing

**Browser Method:**
1. Open `http://127.0.0.1:8000` in browser
2. Open Developer Tools (F12)
3. Go to **Network** tab → Select first request → **Headers** tab
4. Check for these headers:
   - ✅ `Content-Security-Policy`
   - ✅ `X-Frame-Options: DENY`
   - ✅ `X-Content-Type-Options: nosniff`
   - ✅ `X-XSS-Protection: 1; mode=block`
   - ✅ `Referrer-Policy: strict-origin-when-cross-origin`

**Command Line Method:**
```bash
curl -I http://127.0.0.1:8000
```

**Expected Output:**
```
Content-Security-Policy: default-src 'self'; script-src ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

#### 2. HTTPS Enforcement Testing

**Test HTTP to HTTPS Redirect:**
1. Set `FORCE_HTTPS=true` in `.env`
2. Try accessing via HTTP:
```bash
curl -I http://127.0.0.1:8000
```
3. **Expected:** Should redirect to HTTPS (if configured)

**Test HSTS Header:**
1. Access via HTTPS
2. Check for: `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`

#### 3. Two-Factor Authentication Testing

**Enable 2FA (TOTP):**
1. Log in → **Settings** → **Two-Factor Auth**
2. Click **Enable 2FA**
3. Scan QR code with authenticator app (Google Authenticator, Authy)
4. Enter 6-digit code from app
5. **Expected:** 2FA enabled, recovery codes shown

**Test 2FA Login:**
1. Log out
2. Log in with email/password
3. **Expected:** Redirected to 2FA challenge page
4. Enter code from authenticator app
5. **Expected:** Successfully logged in

**Test Email 2FA:**
1. Log out and log in again
2. On 2FA challenge page, check email for 6-digit code
3. Enter email OTP code
4. **Expected:** Successfully logged in

**Test Recovery Codes:**
1. Go to **Settings** → **Two-Factor Auth** → View recovery codes
2. Log out
3. Log in and use a recovery code instead of TOTP
4. **Expected:** Logged in, that recovery code invalidated

**Test Invalid 2FA Code:**
1. Try logging in with invalid 2FA code
2. **Expected:** Error message, login blocked

#### 4. JWT Token Security Testing

**Verify HTTP-Only Cookies:**
1. Log in to your account
2. Open DevTools (F12) → **Application** → **Cookies**
3. Check `access_token` and `refresh_token`:
   - ✅ `HttpOnly` flag checked
   - ✅ `Secure` flag (if HTTPS)
   - ✅ `SameSite` attribute present

**Verify Tokens Not in localStorage:**
1. DevTools → **Application** → **Local Storage**
2. **Expected:** No JWT tokens in localStorage

**Test Token Refresh:**
1. Log in
2. Wait for access token to expire (60 min) OR manually expire
3. Make request to protected route
4. **Expected:** Refresh token automatically generates new access token

#### 5. CSRF Protection Testing

**Verify CSRF Token:**
1. Open any form on website
2. View page source (Cmd+U)
3. Look for: `<meta name="csrf-token" content="...">`
4. **Expected:** CSRF token present

**Test CSRF Protection:**
```bash
curl -X POST http://127.0.0.1:8000/checkout \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```
**Expected:** 419 error (CSRF token mismatch)

#### 6. Session Security Testing

**Check Session Cookie:**
1. Log in
2. DevTools → **Cookies** → Look for `laravel_session`
3. **Expected:**
   - ✅ `HttpOnly` flag
   - ✅ `SameSite=Lax` or `SameSite=Strict`
   - ✅ `Secure` flag (if HTTPS)

**Test Session Regeneration:**
1. Log in, note session ID
2. Log out and log back in
3. **Expected:** Session ID changes

#### 7. Access Control Testing

**Test Admin Routes:**
1. Log in as regular user (not admin)
2. Try accessing: `http://127.0.0.1:8000/admin`
3. **Expected:** 403 Forbidden

**Test Authenticated Routes:**
1. Log out
2. Try accessing: `http://127.0.0.1:8000/dashboard`
3. **Expected:** Redirected to login

#### 8. Rate Limiting Testing

**Test Login Rate Limiting:**
1. Try logging in with wrong credentials 5+ times
2. **Expected:** Rate limited, error shown

**Test 2FA Rate Limiting:**
1. Submit wrong 2FA codes 5+ times
2. **Expected:** Rate limited

#### 9. Input Validation Testing

**Test XSS Protection:**
1. Try submitting form with: `<script>alert('XSS')</script>`
2. **Expected:** Script sanitized/escaped, not executed

**Test SQL Injection Protection:**
1. Try injecting: `' OR '1'='1` in search fields
2. **Expected:** Handled safely by Laravel query builder

---

## OWASP ZAP Testing

### What is OWASP ZAP?

OWASP ZAP (Zed Attack Proxy) is a free security testing tool that helps find vulnerabilities in web applications.

### Prerequisites

1. **Docker** (Recommended) OR **OWASP ZAP Desktop**
2. Application running on `http://localhost:8000`

### Installation

**Option A: Docker (Recommended)**
```bash
docker pull owasp/zap2docker-stable
```

**Option B: Desktop Application**
Download from: https://www.zaproxy.org/download/

### Quick Start

1. **Start ZAP in Docker:**
```bash
docker run -d -p 8080:8080 --name zap owasp/zap2docker-stable zap.sh -daemon -host 0.0.0.0 -port 8080 -config api.disablekey=true
```

2. **Start your Laravel application:**
```bash
php artisan serve
```

3. **Run the automated scan:**
```bash
chmod +x zap-scan.sh
./zap-scan.sh
```

### Scan Process

The script performs:

1. **Spider Scan** (5-10 minutes)
   - Crawls the application
   - Discovers all URLs
   - Maps application structure

2. **Active Scan** (10-30 minutes)
   - Tests for vulnerabilities
   - SQL injection, XSS, CSRF, etc.
   - May take longer for large applications

3. **Report Generation**
   - HTML report (human-readable)
   - JSON report (machine-readable)
   - XML report (for CI/CD)

### Reviewing Results

1. **Open HTML Report:**
```bash
open zap-reports/zap-report-*.html
```

2. **Review Alert Levels:**
   - **High** - Critical vulnerabilities (fix immediately)
   - **Medium** - Important issues (fix soon)
   - **Low** - Minor issues (consider fixing)
   - **Info** - Informational messages

3. **Common Issues to Look For:**
   - Missing security headers
   - XSS vulnerabilities
   - CSRF protection gaps
   - Insecure cookies
   - Information disclosure

### Configuration

Edit `zap-config.xml` to customize:
- Scan policies (High/Medium/Low)
- Context URLs
- Authentication settings
- Excluded URLs

### Environment Variables

```bash
export APP_URL="http://localhost:8000"
export ZAP_HOST="localhost"
export ZAP_PORT="8080"
export ZAP_API_KEY="your-api-key"  # Optional
export REPORT_DIR="./zap-reports"
export TIMEOUT="300"
```

### Manual Testing with ZAP Desktop

1. Start OWASP ZAP Desktop application
2. Configure target URL: `http://localhost:8000`
3. Run **Quick Start** or **Full Scan**
4. Review alerts in the **Alerts** tab
5. Generate reports: **Report** → **Generate Report**

### Critical Endpoints to Test

- `POST /login` - Login endpoint
- `POST /register` - Registration
- `POST /two-factor-challenge` - 2FA challenge
- `POST /checkout` - Checkout process
- `GET /admin/*` - Admin routes
- `POST /admin/loans/*` - Loan management

### What ZAP Tests For

1. **Injection Attacks**
   - SQL Injection
   - Command Injection
   - LDAP Injection

2. **Cross-Site Scripting (XSS)**
   - Reflected XSS
   - Stored XSS
   - DOM-based XSS

3. **Authentication & Session**
   - Weak passwords
   - Session fixation
   - Cookie security

4. **Security Headers**
   - CSP (Content Security Policy)
   - X-Frame-Options
   - HSTS
   - X-Content-Type-Options

5. **Sensitive Data Exposure**
   - Information disclosure
   - Insecure storage

6. **Access Control**
   - Privilege escalation
   - Insecure direct object references

### Troubleshooting ZAP

**ZAP Not Responding:**
- Check if ZAP is running: `curl http://localhost:8080`
- Verify port 8080 is not in use
- Check firewall settings

**Application Not Accessible:**
- Verify Laravel is running: `php artisan serve`
- Check `APP_URL` in scan script
- Verify network connectivity

**Scan Taking Too Long:**
- Reduce scan scope in `zap-config.xml`
- Exclude static assets
- Use targeted scans instead of full scans

---

## Troubleshooting

### Issue: CSP Blocking Scripts

**Symptoms:** Black screen, JavaScript errors in console

**Solution:**
1. Check `app/Http/Middleware/SecurityHeadersMiddleware.php`
2. Verify CSP allows Vite dev server in development
3. Check browser console for CSP violations

**Development CSP:**
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173"
"connect-src 'self' https: ws: wss: http://localhost:* http://127.0.0.1:*"
```

### Issue: 2FA Not Working

**Symptoms:** 2FA codes not accepted, emails not sent

**Solution:**
1. Check email configuration in `config/mail.php`
2. Verify `EmailTwoFactorService` is working:
```bash
php artisan tinker
>>> app(\App\Services\EmailTwoFactorService::class)->sendOtp(\App\Models\User::first());
```
3. Check database for `email_2fa_code` column:
```bash
php artisan migrate
```
4. Verify email service is configured correctly

### Issue: JWT Tokens Not Working

**Symptoms:** Tokens not in cookies, authentication failing

**Solution:**
1. Check `JwtService` is properly configured
2. Verify `JwtCookieMiddleware` is registered in `bootstrap/app.php`
3. Check cookie settings in browser DevTools
4. Verify `APP_KEY` is set in `.env`

### Issue: OWASP ZAP Scan Fails

**Symptoms:** Script errors, connection refused

**Solution:**
1. Ensure ZAP is running:
```bash
docker ps | grep zap
```
2. Check application is accessible:
```bash
curl http://localhost:8000
```
3. Verify network connectivity
4. Check ZAP logs:
```bash
docker logs zap
```

### Issue: Security Headers Missing

**Symptoms:** Headers not present in response

**Solution:**
1. Verify `SecurityHeadersMiddleware` is registered:
```php
// bootstrap/app.php
$middleware->web(append: [
    SecurityHeadersMiddleware::class,
    // ...
]);
```
2. Clear config cache:
```bash
php artisan config:clear
php artisan route:clear
```

### Issue: HTTPS Redirect Not Working

**Symptoms:** HTTP not redirecting to HTTPS

**Solution:**
1. Set `FORCE_HTTPS=true` in `.env`
2. Verify `ForceHttpsMiddleware` is registered
3. Check `APP_URL` uses `https://`
4. In production, ensure SSL certificate is installed

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `FORCE_HTTPS=true` in `.env`
- [ ] Configure `APP_URL=https://yourdomain.com`
- [ ] Set up SSL certificate
- [ ] Configure email service for 2FA OTP
- [ ] Set up Azure AD credentials (if using)
- [ ] Run database migrations
- [ ] Run OWASP ZAP scan
- [ ] Fix all high/medium vulnerabilities
- [ ] Test all security features
- [ ] Review security headers
- [ ] Test 2FA functionality
- [ ] Verify JWT tokens work
- [ ] Test CSRF protection
- [ ] Verify admin route protection

### Production Configuration

**`.env` file:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
FORCE_HTTPS=true

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Email (for 2FA)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Azure AD (if using)
MICROSOFT_CLIENT_ID=your-client-id
MICROSOFT_CLIENT_SECRET=your-client-secret
MICROSOFT_TENANT_ID=your-tenant-id
```

### Security Hardening

1. **Remove Development CSP Rules:**
   - Remove `'unsafe-inline'` and `'unsafe-eval'` from CSP
   - Use nonces or hashes for inline scripts

2. **Enable All Security Features:**
   - Ensure all middleware is active
   - Verify security headers are set
   - Test HTTPS enforcement

3. **Database Security:**
   - Use strong database passwords
   - Limit database user permissions
   - Enable database encryption

4. **Server Security:**
   - Keep PHP and Laravel updated
   - Use firewall rules
   - Enable server-level security headers
   - Regular security updates

---

## Maintenance & Audits

### Regular Security Tasks

**Weekly:**
- Review application logs for suspicious activity
- Check for failed login attempts
- Monitor rate limiting

**Monthly:**
- Run OWASP ZAP scan
- Review security headers
- Test 2FA functionality
- Check for Laravel/PHP updates

**Quarterly:**
- Full security audit
- Review and update security policies
- Test all security features
- Review access logs

### Security Audit Checklist

- [ ] All security headers present
- [ ] HTTPS enforced
- [ ] 2FA working correctly
- [ ] JWT tokens secure
- [ ] CSRF protection active
- [ ] Rate limiting working
- [ ] Admin routes protected
- [ ] No critical vulnerabilities (OWASP ZAP)
- [ ] Passwords properly hashed
- [ ] Session security configured
- [ ] Input validation working
- [ ] XSS protection active
- [ ] SQL injection protection active

### Updating Security Measures

1. **Keep Dependencies Updated:**
```bash
composer update
npm update
```

2. **Monitor Security Advisories:**
- Laravel Security Advisories
- PHP Security Advisories
- OWASP Top 10 updates

3. **Review Code Changes:**
- Code review for security issues
- Test new features for vulnerabilities
- Update security tests

---

## Comprehensive Security Checklist

Use this checklist to verify all security measures:

- [ ] Security headers are present (CSP, X-Frame-Options, etc.)
- [ ] HTTPS enforcement works (in production)
- [ ] 2FA can be enabled and works (TOTP)
- [ ] Email 2FA works
- [ ] Recovery codes work
- [ ] JWT tokens are in HTTP-Only cookies (not localStorage)
- [ ] Tokens are not exposed in response headers
- [ ] CSRF protection is active
- [ ] Session cookies are secure (HttpOnly, SameSite)
- [ ] Rate limiting works
- [ ] Admin routes are protected
- [ ] XSS protection works
- [ ] SQL injection protection works
- [ ] Password hashing is secure
- [ ] OWASP ZAP scan completed
- [ ] No critical/high vulnerabilities found
- [ ] Email service configured for 2FA
- [ ] Azure AD configured (if using)
- [ ] File permissions correct
- [ ] Debug mode disabled in production

---

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP ZAP Documentation](https://www.zaproxy.org/docs/)
- [Microsoft Identity Platform](https://learn.microsoft.com/en-us/entra/identity-platform/)
- [JWT Best Practices](https://datatracker.ietf.org/doc/html/rfc8725)

---

## Quick Reference

### Test Scripts

```bash
# Quick security test
./test-security.sh

# OWASP ZAP scan
./zap-scan.sh

# Check security headers
curl -I http://127.0.0.1:8000
```

### Important Files

- `app/Http/Middleware/SecurityHeadersMiddleware.php` - Security headers
- `app/Http/Middleware/RequireTwoFactor.php` - 2FA enforcement
- `app/Services/JwtService.php` - JWT token generation
- `app/Services/EmailTwoFactorService.php` - Email 2FA
- `zap-config.xml` - OWASP ZAP configuration
- `zap-scan.sh` - Automated scan script

### Key Commands

```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Run migrations
php artisan migrate

# Generate wayfinder routes (if needed)
php artisan wayfinder:generate --with-form

# Test email 2FA
php artisan tinker
>>> app(\App\Services\EmailTwoFactorService::class)->sendOtp(\App\Models\User::first());
```

---

**Last Updated:** December 2025
**Version:** 1.0

