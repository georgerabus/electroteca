# Electroteca  
A policy-driven electronics lending platform

## 📌 Overview
Electroteca is a full-stack web application designed to enable secure, transparent, and efficient lending of electronic devices within academic and professional communities. The platform introduces a policy-driven lending model that combines institutional inventory management—where universities publish and govern their own devices—with verified peer-to-peer contributions, allowing individuals to list personal equipment and earn credits when those devices are borrowed.

The system emphasizes trust, accountability, and safety through strong security controls, user verification, device condition logging, escrow-based payments, reputation tracking, and calibration records. Electroteca provides a modern and scalable solution for managing shared electronic resources responsibly.
## Features

### 🔐 User Authentication & Security
- Secure Sign Up and Login
- Email-based OTP verification
- Two-Factor Authentication (TOTP & Email OTP)
- Google OAuth and Microsoft Azure AD integration
- Role-Based Access Control (RBAC)
- Custom JWT authentication using HTTP-Only cookies

### 📦 Device Catalog & Lending
- Institutional and peer-owned device listings
- Advanced search and filtering
- Reservation system
- Check-out and check-in workflows
- Device availability tracking

### 💳 Payments & Escrow
- Escrow-based lending protection
- Hybrid payment model
- Wallet credits for users
- Automatic deductions for damages or late returns

### ⭐ Reputation & Trust
- User reputation system
- Device condition logging
- Damage reports and dispute handling
- Event-driven reputation updates

### 💬 Communication & Notifications
- Automated reminders for returns
- Lending status notifications
- Internal system messaging

### 🛠 Administration
- Inventory governance for institutions
- User moderation tools
- System monitoring and management

---

## Tech Stack

**Frontend:** React 19, Inertia.js, Vite, Tailwind CSS, TypeScript, Radix UI.  

**Backend:** PHP 8.2, Laravel 12, Laravel Fortify, Laravel Socialite, Laravel Cashier (Paddle).  

**Database:** SQLite (default), MySQL, or PostgreSQL.  

**DevOps & Tooling:** Docker & Laravel Sail, Vite build system, Pest (Testing), OWASP ZAP (Security Testing).

## Quick Start (Local)

Requirements:
- PHP 8.2+
- Composer
- Node 20+ and npm
- SQLite (default) or MySQL/PostgreSQL

Setup and run:
```bash
composer run setup
composer run dev
```

The setup script creates the sqlite database file, generates the app key, runs migrations and seeds, and builds the frontend.

## Default Credentials (Development)

After `php artisan db:seed` (or `composer run setup`), use:
- Email: admin@example.com
- Password: Password123!

These values come from `.env.example` and are controlled by `DEMO_USER_*` variables. Disable in production by setting `DEMO_USER_ENABLED=false`.

## Testing

```bash
composer test
```

## CI/CD

GitHub Actions workflow: `.github/workflows/ci.yml`
- Installs PHP and JS dependencies
- Builds the frontend
- Runs the test suite
- Fails on any test or build error

## Deployment (VPS via SSH)

Workflow: `.github/workflows/deploy.yml` (runs on push to `main` when secrets are set).

Required GitHub Secrets:
- VPS_HOST
- VPS_USER
- VPS_SSH_KEY
- VPS_PORT (optional, defaults to 22)
- VPS_PATH (absolute path to the repo on the server)

Server prerequisites:
- PHP 8.2+, Composer
- Node 20+ and npm
- A configured `.env` file on the server

Recommended production settings:
- APP_ENV=production
- APP_DEBUG=false
- FORCE_HTTPS=true
- CSP_ALLOW_DEV=false
- SESSION_SECURE_COOKIE=true

## Health Check

`GET /api/health` returns a JSON status payload.

## 🔐 Security Overview

Electroteca follows OWASP best practices and implements a security-first architecture.

Implemented security measures include:
- Global security headers (CSP, HSTS, X-Frame-Options, XSS protection)
- HTTPS enforcement and SSL support
- Two-Factor Authentication (TOTP and Email OTP)
- Secure JWT authentication using HTTP-Only cookies
- CSRF protection and secure session handling
- Rate limiting for login and 2FA attempts
- Input validation and protection against XSS and SQL injection
- Automated vulnerability scanning using OWASP ZAP

📄 Full details are available in **`SECURITY_GUIDE.md`**

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ & npm
- Docker & Docker Compose (recommended)

---

## 1. Environment Setup

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the following in `.env`:

- Database credentials  
- OAuth (Google / Azure AD)  
- Email configuration (for 2FA)  
- Payment configuration (Paddle)  
- HTTPS enforcement (production)

---

## 2. Running with Docker (Recommended)

Build and start the application:

```bash
docker compose up --build
```

Access the application:

- **Web App:** http://localhost  
- **API:** http://localhost/api  

Stop the containers:

```bash
docker compose down
```

---

## 3. Running Locally (Development Mode)

### Backend (Laravel)

```bash
composer install
php artisan migrate
php artisan serve
```

### Frontend (React + Vite)

```bash
npm install
npm run dev
```

---

## 🧪 Running Tests

### Backend Tests

```bash
php artisan test
```

### Security Testing (OWASP ZAP)

```bash
./zap-scan.sh
```
## 📚 Documentation

Additional documentation available in the repository:

- `SECURITY_GUIDE.md`
- `ESCROW_SYSTEM_GUIDE.md`
- `PAYMENT_SETUP_GUIDE.md`
- `REPUTATION.md`
- `IMPLEMENTATION_SUMMARY.md`

