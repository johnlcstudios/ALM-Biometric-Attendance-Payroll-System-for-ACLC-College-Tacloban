# ALM Biometrics - Updated Installer Guide

## What's New in This Version

### ✅ Automatic Database Setup
The installer now automatically:
- Creates the `alm_biometrics` database
- Runs the main schema (schema.sql)
- Applies all migrations (001, 002, 003) in order
- Sets up all required tables and indexes

### ✅ Application Icon
Both the Installer and Launcher now have a professional ALM-branded icon with:
- Purple background (#1e0178 - matching system theme)
- White "ALM" text
- 64x64 resolution for crisp display

### ✅ Enhanced Features
- Database setup is optional (checkbox enabled by default)
- Progress indication during database setup
- Detailed status messages for each migration
- Error handling for already-applied migrations

## How to Build the Executables

### Prerequisites
- Windows OS
- .NET Framework 4.0 or higher (included in Windows)
- PowerShell (for icon generation)

### Build Steps

1. **Navigate to the web-app folder:**
   ```
   cd C:\xampp\htdocs\updated biometrics\main3\ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban\web-app
   ```

2. **Run the build script:**
   ```
   build-executable.bat
   ```

   The script will:
   - Generate the ALM icon (if not exists)
   - Compile ALM-Installer.exe with icon
   - Compile ALM-Launcher.exe with icon
   - Display success/error messages

3. **Output files:**
   - `ALM-Installer.exe` - The installation program
   - `ALM-Launcher.exe` - The application launcher
   - Both will have the ALM purple icon

## How to Use the Installer

### Installation Steps

1. **Run ALM-Installer.exe** (as Administrator recommended)

2. **Configure paths:**
   - **Source System Files**: Auto-detected (parent directory of installer)
   - **XAMPP htdocs Path**: Default is `C:\xampp\htdocs`

3. **Database Setup (Recommended):**
   - ✅ "Setup database and run migrations" checkbox is checked by default
   - This will:
     - Create `alm_biometrics` database
     - Run `schema.sql` to create all tables
     - Apply migration 001: Security improvements (password_resets, login_attempts)
     - Apply migration 002: Company code and password reset updates
     - Apply migration 003: Profile picture column

4. **Click "Install Application"**

5. **Wait for completion:**
   - Files will be copied to `htdocs\ALM-Biometrics\`
   - Database will be set up (if checkbox is checked)
   - Desktop shortcut will be created

6. **Launch the application:**
   - Double-click the "ALM Biometrics" desktop shortcut
   - Or run `ALM-Launcher.exe` from the installation folder

## Database Setup Details

### What Gets Created

**Main Schema (schema.sql):**
- companies
- users
- employees
- attendance
- payroll
- And all other core tables

**Migration 001 (001_security_improvements.sql):**
- password_resets table (for forgot password)
- login_attempts table (for rate limiting)

**Migration 002 (002_company_code_and_password_reset.sql):**
- Adds company_code column to companies table
- Generates unique codes for existing companies
- Ensures password_resets and login_attempts tables exist

**Migration 003 (003_add_profile_picture.sql):**
- Adds profile_picture column to employees table
- Creates index for performance

### MySQL Path Detection

The installer automatically looks for MySQL in:
- `C:\xampp\mysql\bin\mysql.exe`
- `D:\xampp\mysql\bin\mysql.exe`
- `E:\xampp\mysql\bin\mysql.exe`

If MySQL is not found, you'll get an error message.

## Troubleshooting

### Build Issues

**Problem**: ".NET Framework compiler not found"
- **Solution**: Ensure .NET Framework 4.0+ is installed (should be on all Windows 7+)

**Problem**: Icon generation fails
- **Solution**: The .ico file is already provided. You can skip generation.

### Installation Issues

**Problem**: "MySQL not found"
- **Solution**: 
  1. Ensure XAMPP is installed
  2. Check if MySQL is running in XAMPP Control Panel
  3. Verify MySQL exists in `C:\xampp\mysql\bin\mysql.exe`

**Problem**: Database setup fails
- **Solution**:
  1. Uncheck "Setup database" during installation
  2. Manually run the migration: Visit `http://localhost/ALM-Biometrics/run-migration.php`
  3. Or manually import SQL files via phpMyAdmin

**Problem**: "Duplicate entry" errors
- **Solution**: This is normal - it means migrations were already applied. The installer handles this gracefully.

### Launcher Issues

**Problem**: Launcher doesn't open browser
- **Solution**: 
  1. Ensure XAMPP Apache is running
  2. Try opening `http://localhost/ALM-Biometrics/` manually
  3. Check if Edge or Chrome is installed

## File Structure

```
web-app/
├── Installer.cs              # Installer source code
├── Launcher.cs               # Launcher source code
├── ALM-Icon.ico             # Application icon (purple with "ALM")
├── generate-icon.ps1        # PowerShell script to generate icon
├── create-icon.vbs          # VBScript alternative for icon generation
├── build-executable.bat     # Build script (compiles both executables)
├── ALM-Installer.exe        # Compiled installer (output)
└── ALM-Launcher.exe         # Compiled launcher (output)
```

## Technical Details

### Installer Features
- **File Copy**: Recursively copies all system files to htdocs
- **Database Setup**: Uses MySQL CLI to execute SQL files
- **Migration Order**: Sorts migration files alphabetically (001, 002, 003)
- **Error Handling**: Gracefully handles duplicate entries and warnings
- **Shortcut Creation**: Uses VBScript to create desktop shortcut
- **UI Updates**: Uses thread-safe Invoke for progress updates

### Icon Generation
The icon is generated using:
- .NET System.Drawing library
- 64x64 bitmap with purple background
- "ALM" text in white, bold Arial 24pt
- Converted to .ico format

### Compiler Flags
```
-target:winexe     # Windows application (no console)
-win32icon:file    # Embed icon in executable
```

## Migration Guide (For Future Updates)

When adding new migrations:

1. **Create SQL file** in `sql/migrations/`:
   ```
   004_your_migration_name.sql
   ```

2. **Number sequentially**: 004, 005, 006, etc.

3. **Include USE statement**:
   ```sql
   USE alm_biometrics;
   -- Your SQL here
   ```

4. **Test the migration**:
   ```bash
   mysql -u root alm_biometrics < 004_your_migration_name.sql
   ```

5. **Update this guide** with the new migration details.

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the INSTALLER_GUIDE.md in the main directory
3. Check XAMPP and MySQL logs
4. Ensure all prerequisites are met

---

**Version**: 2.0  
**Last Updated**: April 2026  
**Compatible With**: XAMPP 7.4+ on Windows
