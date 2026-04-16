# Password Reset Implementation Guide

## Overview
The password reset system has been updated to use **Employee ID** and **Company Code** instead of email-based verification.

## Changes Made

### 1. Database Changes
- Added `company_code` column to `companies` table
- Auto-generates company codes for existing companies (format: CC00001, CC00002, etc.)
- Migration file: `sql/migrations/002_company_code_and_password_reset.sql`

### 2. API Changes (`backend/api.php`)
- **forgot_password** endpoint now requires:
  - `employee_id`: Employee's ID (e.g., EMP001)
  - `company_code`: Company's unique code
- Returns employee name and username upon successful verification
- Generates secure token for password reset

- **reset_password_with_token** endpoint:
  - Validates the token
  - Updates password
  - Marks token as used

### 3. Login Page (`login.php`)
Updated forgot password UI with multi-step process:
1. **Step 1**: Enter Employee ID and Company Code
2. **Step 2**: System verifies credentials
3. **Step 3**: Shows verified employee name and username, asks for new password
4. **Step 4**: Resets password and confirms success

### 4. ESS Portal (`ess.php`)
Added company code display in multiple locations:

#### Dashboard
- New stat card showing Company Code at the top of the stats grid

#### Profile Page - Summary Card
- Shows Company Code between Employee ID and Department

#### Profile Page - Personal Information Tab
- Added Employee ID field
- Added Company Code field (highlighted in primary color)

### 5. Session Management
- Login now stores `company_code` in session: `$_SESSION['company_code']`
- Available across all authenticated pages

## How to Use

### For Administrators
1. Run the migration to add company_code column:
   ```bash
   mysql -u root -p alm_biometrics < "sql/migrations/002_company_code_and_password_reset.sql"
   ```

2. Check company codes in the database:
   ```sql
   SELECT id, name, company_code FROM companies;
   ```

3. Communicate company codes to employees for password reset purposes

### For Employees
**To Reset Password:**
1. Click "Forgot Password?" on login page
2. Enter your Employee ID (e.g., EMP001)
3. Enter your Company Code (e.g., CC00001)
4. System will verify and show your name and username
5. Enter new password (minimum 6 characters)
6. Password is immediately updated

**To Find Your Company Code:**
- **ESS Dashboard**: Look at the first stat card "Company Code"
- **Profile Page**: View in the summary card or Personal Information tab
- Contact HR/Admin if you don't have access to the system

## Security Features

1. **Token-based Reset**: Secure random tokens with 1-hour expiration
2. **One-time Use**: Tokens can only be used once
3. **No Information Disclosure**: Generic error messages prevent enumeration
4. **Rate Limiting**: Login attempts are rate-limited (5 attempts, 15-minute lockout)
5. **Password Requirements**: Minimum 6 characters enforced

## Database Schema

### companies table (updated)
```sql
ALTER TABLE companies 
ADD COLUMN company_code VARCHAR(20) UNIQUE NOT NULL;
```

### password_resets table
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Testing

### Test Password Reset Flow
1. Navigate to login page
2. Click "Forgot Password?"
3. Enter valid Employee ID and Company Code
4. Verify employee information is displayed
5. Enter new password (6+ characters)
6. Login with new credentials

### Test Company Code Display
1. Login to ESS portal
2. Check dashboard - Company Code should appear in first stat card
3. Navigate to "My Profile"
4. Verify Company Code appears in summary card
5. Click "Personal Information" tab
6. Verify Company Code field is displayed

## Notes
- Company codes are auto-generated for existing companies
- Format: CC followed by 5-digit ID (CC00001, CC00002, etc.)
- Company codes are unique and indexed for fast lookups
- The .env file already has Brevo API key configured for future email notifications
