Rem VBScript to create a simple icon
Rem This creates a basic icon using PowerShell
Set objShell = CreateObject("WScript.Shell")
strCommand = "powershell -Command ""Add-Type -AssemblyName System.Drawing; $bitmap = New-Object System.Drawing.Bitmap(64, 64); $graphics = [System.Drawing.Graphics]::FromImage($bitmap); $graphics.Clear([System.Drawing.Color]::FromArgb(30, 1, 120)); $font = New-Object System.Drawing.Font('Arial', 24, [System.Drawing.FontStyle]::Bold); $brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White); $graphics.DrawString('ALM', $font, $brush, 2, 15); $icon = [System.Drawing.Icon]::FromHandle($bitmap.GetHicon()); $stream = New-Object System.IO.FileStream('ALM-Icon.ico', [System.IO.FileMode]::Create); $icon.Save($stream); $stream.Close(); $graphics.Dispose(); $bitmap.Dispose(); $icon.Dispose();"""
objShell.Run strCommand, 0, True
