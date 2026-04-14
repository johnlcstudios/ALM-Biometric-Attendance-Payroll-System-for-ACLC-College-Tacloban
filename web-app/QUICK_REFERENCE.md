# Quick Reference - ALM Installer & Launcher

## 🎯 What's New?

### ✅ Auto Database Setup
- Creates database automatically
- Runs all migrations (001, 002, 003)
- One-click complete installation

### ✅ Professional Icons
- Purple ALM-branded icon
- Embedded in both executables
- Matches system theme

## 🚀 Quick Start

### Build Executables
```powershell
cd web-app
.\build-executable.bat
```

### Install Application
1. Run `ALM-Installer.exe`
2. Keep "Setup database" checked ✓
3. Click "Install"
4. Done! 🎉

## 📁 Output Files

| File | Size | Purpose |
|------|------|---------|
| ALM-Installer.exe | 16 KB | Installer with icon |
| ALM-Launcher.exe | 7.6 KB | Launcher with icon |
| ALM-Icon.ico | 2.6 KB | App icon |

## 🔧 Files Modified

- **Installer.cs** - Added database setup
- **Launcher.cs** - Added icon support
- **build-executable.bat** - Added icon embedding

## 📝 Key Features

### Installer Now:
✅ Copies files to htdocs  
✅ Creates `alm_biometrics` database  
✅ Runs schema.sql  
✅ Applies migration 001 (security)  
✅ Applies migration 002 (company code)  
✅ Applies migration 003 (profile picture)  
✅ Creates desktop shortcut  
✅ Shows progress for each step  

### Both Executables:
✅ Have purple ALM icon  
✅ Show icon in file explorer  
✅ Show icon on taskbar  
✅ Show icon in Alt+Tab  

## ⚙️ Requirements

- Windows 7+
- XAMPP installed
- MySQL running
- .NET Framework 4.0+

## 🐛 Troubleshooting

**MySQL not found?**
→ Check XAMPP is installed at C:\xampp

**Build fails?**
→ Run as Administrator

**Database setup fails?**
→ Uncheck database option, run manually later

## 📚 Documentation

- Full guide: `INSTALLER_GUIDE.md`
- Update summary: `INSTALLER_UPDATE_SUMMARY.md`
- Icon generator: `generate-icon.ps1`

---

**Status**: ✅ Complete & Ready  
**Version**: 2.0  
**Date**: April 14, 2026
