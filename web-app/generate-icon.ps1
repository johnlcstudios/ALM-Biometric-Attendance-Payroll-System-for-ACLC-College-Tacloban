# Generate ALM Icon
Add-Type -AssemblyName System.Drawing

# Create bitmap
$bitmap = New-Object System.Drawing.Bitmap(64, 64)
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.Clear([System.Drawing.Color]::FromArgb(30, 1, 120))

# Add text
$font = New-Object System.Drawing.Font('Arial', 24, [System.Drawing.FontStyle]::Bold)
$brush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
$graphics.DrawString('ALM', $font, $brush, 2, 15)

# Convert to icon and save
$icon = [System.Drawing.Icon]::FromHandle($bitmap.GetHicon())
$stream = New-Object System.IO.FileStream('ALM-Icon.ico', [System.IO.FileMode]::Create)
$icon.Save($stream)

# Cleanup
$stream.Close()
$graphics.Dispose()
$bitmap.Dispose()
$icon.Dispose()

Write-Host "Icon created successfully!" -ForegroundColor Green
