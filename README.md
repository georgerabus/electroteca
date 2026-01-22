# Electroteca  
A policy-driven electronics lending platform

## Overview
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

**Database:** MySQL or PostgreSQL.  

**DevOps & Tooling:** Docker & Laravel Sail, Vite build system, Pest (Testing), OWASP ZAP (Security Testing).

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

