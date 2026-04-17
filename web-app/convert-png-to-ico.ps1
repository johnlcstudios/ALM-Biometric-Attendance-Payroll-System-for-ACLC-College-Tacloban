# Convert ALM-Icon.png to ALM-Icon.ico
Add-Type -AssemblyName System.Drawing

$pngPath = Join-Path $PSScriptRoot "ALM-Icon.png"
$icoPath = Join-Path $PSScriptRoot "ALM-Icon.ico"

if (-not (Test-Path $pngPath)) {
    Write-Error "ALM-Icon.png not found!"
    exit 1
}

try {
    $img = [System.Drawing.Image]::FromFile($pngPath)
    $bmp = New-Object System.Drawing.Bitmap($img, 256, 256)
    $icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
    $fs = New-Object System.IO.FileStream($icoPath, 'Create')
    $icon.Save($fs)
    $fs.Close()
    $icon.Dispose()
    $bmp.Dispose()
    $img.Dispose()
    
    if (Test-Path $icoPath) {
        Write-Output "Success: ALM-Icon.ico created"
        exit 0
    } else {
        Write-Error "Failed to create icon file"
        exit 1
    }
} catch {
    Write-Error "Conversion failed: $_"
    exit 1
}
