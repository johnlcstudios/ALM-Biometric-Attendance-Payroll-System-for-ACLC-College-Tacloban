@echo off
echo ========================================
echo Fix Role Enum for SD Pages
echo ========================================
echo.
echo This will update the database to add
echo 'SD' and 'School Director' roles.
echo.
pause

echo.
echo Running database migration...
echo.

php -f fix_role_enum.php

echo.
echo.
echo ========================================
echo Migration Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Visit signup.php
echo 2. Create a new account
echo 3. Login with your credentials
echo 4. You will be redirected to SD Pages!
echo.
pause
