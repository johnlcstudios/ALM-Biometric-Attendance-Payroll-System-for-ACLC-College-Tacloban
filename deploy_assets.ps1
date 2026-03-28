# Deployment and Environment Setup Script
# Sets up the necessary directories and permissions for the kiosk

$BaseDir = "C:\xampp\htdocs\v4\ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban"
$LogDir = Join-Path $BaseDir "logs"
$ModelsDir = Join-Path $BaseDir "models"

Write-Host "Initializing Environment..." -ForegroundColor Cyan

if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir
    Write-Host "Created Logs directory."
}

if (-not (Test-Path $ModelsDir)) {
    Write-Warning "Models directory missing! Facial recognition will fail."
}

# Set permissions for web server
# icacls $BaseDir /grant "Everyone:(OI)(CI)F" /T

Write-Host "Setup Complete." -ForegroundColor Green
