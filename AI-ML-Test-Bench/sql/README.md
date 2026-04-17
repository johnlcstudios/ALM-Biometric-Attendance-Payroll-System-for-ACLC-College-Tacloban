# Database Setup Guide

## Overview

This directory contains all database schema and migration files for the ALM Biometric Attendance & Payroll System.

## Files

### 📄 `complete_schema.sql` ⭐ **RECOMMENDED**
- **Complete database schema with ALL migrations included**
- Use this for **NEW installations**
- Contains: Base schema + Migrations 001-004
- **One-file setup** - no need to run individual migrations
- Version: 2.5.0 - 

### 📁 `migrations/` (Individual Migration Files)
- `001_security_improvements.sql` - Password resets & login attempts tables
- `002_audit_trail_and_security.sql` - Audit logging & 2FA support
- `002_company_code_and_password_reset.sql` - Company code & password reset (duplicate)
- `003_add_profile_picture.sql` - Profile picture column
- `004_alm_features_v2.4.sql` - Faculty level, hire date, resignation tracking, reinstatement

### 📄 `schema.sql`
- Original base schema (without migrations)
- Kept for reference only

## Quick Start

### For New Installation (Recommended)

1. **Using phpMyAdmin:**
   - Open phpMyAdmin
   - Click "Import" tab
   - Select `complete_schema.sql`
   - Click "Go"

2. **Using MySQL Command Line:**
   ```bash
   mysql -u root -p < sql/complete_schema.sql
   ```

3. **Using the Installer:**
   - Run `setup-db.php` or `secure-setup.php`
   - The installer automatically uses the complete schema

### For Existing Installation

If you already have the database set up and want to apply only new migrations:

1. **Using the Installer (Recommended):**
   - Run `setup-db.php` - it automatically detects and runs only unapplied migrations

2. **Manual Migration:**
   - Run only `004_alm_features_v2.4.sql` (latest migration)
   - Or use the migration tracking system

## Schema Version

- **Current Version:** 2.5.0 - 
- **Last Updated:** April 2026
- **Database:** alm_biometrics

## What's Included

### Base Tables
- ✅ companies
- ✅ users (with 2FA support)
- ✅ employees (with faculty_level, hire_date, profile_picture)
- ✅ attendance
- ✅ payroll
- ✅ leave_requests
- ✅ loans
- ✅ resignations (with decline tracking)
- ✅ deductions
- ✅ allowance_categories
- ✅ employee_allowances
- ✅ employee_deductions
- ✅ subjects
- ✅ subject_loads

### Security & Audit Tables
- ✅ password_resets
- ✅ login_attempts
- ✅ audit_log
- ✅ user_sessions
- ✅ migrations (tracking table)

### Features in v2.4
- ✅ Faculty Level (SHS, College, Both)
- ✅ Hire Date tracking
- ✅ Resignation decline functionality
- ✅ Employee reinstatement tracking
- ✅ Profile picture support
- ✅ 2FA authentication
- ✅ Audit trail logging
- ✅ Rate limiting
- ✅ Password reset functionality

## Migration History

| Version | File | Description |
|---------|------|-------------|
| Base | schema.sql | Initial database schema |
| 001 | 001_security_improvements.sql | Password resets & login attempts |
| 002 | 002_audit_trail_and_security.sql | Audit logging, 2FA, sessions |
| 002 | 002_company_code_and_password_reset.sql | Company code (duplicate) |
| 003 | 003_add_profile_picture.sql | Profile picture column |
| 004 | 004_alm_features_v2.4.sql | Faculty level, hire date, resignation tracking |
| **2.5.0** | **complete_schema.sql** | **ALL-in-ONE complete schema** ⭐ |

## Notes

1. **complete_schema.sql** is the recommended file for new installations
2. Individual migration files are kept for reference and incremental updates
3. The installer (`setup-db.php`) automatically handles migrations
4. All migrations are idempotent (safe to run multiple times)
5. Never use default passwords in production

## Support

For issues or questions, refer to:
- `INSTALLATION_GUIDE.md` - Detailed installation steps
- `README.md` - System overview
- `Changelog.md` - Version history
