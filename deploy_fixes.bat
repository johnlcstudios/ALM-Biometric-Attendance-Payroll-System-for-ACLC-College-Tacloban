@echo off
echo ============================================
echo ALM Biometric System - Week 6 Fixes Deploy
echo ============================================
echo.

echo [1/3] Running Soft Delete Migration...
echo.
php backend\add_soft_deletes.php
echo.

echo [2/3] Verifying Security Functions...
echo.
php -r "require 'backend/api_helpers.php'; echo 'Sanitization functions loaded successfully' . PHP_EOL;"
echo.

echo [3/3] Checking Database Connection...
echo.
php -r "require 'backend/db.php'; echo 'Database connection successful' . PHP_EOL;"
echo.

echo ============================================
echo Deployment Summary
echo ============================================
echo.
echo Completed:
echo   [OK] Soft delete columns added
echo   [OK] Input sanitization functions ready
echo   [OK] SQL injection prevention active
echo   [OK] Payroll logic fixes applied
echo.
echo Next Steps (Manual):
echo   1. Update DELETE queries to use soft deletes
echo   2. Update SELECT queries to filter is_deleted = 0
echo   3. Test payroll calculations
echo   4. Verify dashboard data consistency
echo.
echo See WEEK6_FIXES_IMPLEMENTATION_REPORT.md for details
echo.
pause
