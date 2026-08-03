param(
    [string]$Source = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$Target = 'C:\xampp\htdocs\IAmStillHere',
    [switch]$Once,
    [int]$IntervalSeconds = 1
)

$ErrorActionPreference = 'Stop'

$excludeDirs = @(
    '.git',
    '.codex',
    '.agents',
    'node_modules',
    'vendor'
)

$excludeFilePatterns = @(
    '*.tmp',
    '*.log',
    '*.bak',
    '*.swp',
    '~$*',
    'xampp-sync.pid',
    'xampp-sync.log'
)

function Get-RelativeProjectPath {
    param([string]$FullPath)

    $sourceRoot = [System.IO.Path]::GetFullPath($Source).TrimEnd([char[]]@('\', '/')) + [System.IO.Path]::DirectorySeparatorChar
    $absolutePath = [System.IO.Path]::GetFullPath($FullPath)

    if ($absolutePath.StartsWith($sourceRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $absolutePath.Substring($sourceRoot.Length)
    }

    return Split-Path $FullPath -Leaf
}

function Test-IsExcludedPath {
    param([string]$FullPath)

    $relative = Get-RelativeProjectPath -FullPath $FullPath
    $parts = $relative -split '[\\/]+'

    foreach ($dir in $excludeDirs) {
        if ($parts -contains $dir) {
            return $true
        }
    }

    $name = Split-Path $FullPath -Leaf
    foreach ($pattern in $excludeFilePatterns) {
        if ($name -like $pattern) {
            return $true
        }
    }

    return $false
}

function Get-SyncableFiles {
    Get-ChildItem -LiteralPath $Source -Recurse -File -Force |
        Where-Object { -not (Test-IsExcludedPath -FullPath $_.FullName) }
}

function Copy-ChangedFile {
    param([string]$FullPath)

    if (-not (Test-Path -LiteralPath $FullPath -PathType Leaf)) {
        return
    }

    if (Test-IsExcludedPath -FullPath $FullPath) {
        return
    }

    $relative = Get-RelativeProjectPath -FullPath $FullPath
    $destination = Join-Path $Target $relative
    $destinationDir = Split-Path $destination -Parent

    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    Copy-Item -LiteralPath $FullPath -Destination $destination -Force
    Write-Host "Synced: $relative" -ForegroundColor Green
}

function Sync-All {
    Write-Host "Syncing repo to XAMPP..." -ForegroundColor Cyan
    Write-Host "Source: $Source"
    Write-Host "Target: $Target"

    if (-not (Test-Path -LiteralPath $Target)) {
        New-Item -ItemType Directory -Path $Target -Force | Out-Null
    }

    Get-SyncableFiles | ForEach-Object { Copy-ChangedFile -FullPath $_.FullName }
    Write-Host "Initial sync complete." -ForegroundColor Cyan
}

function Get-FileStampKey {
    param([System.IO.FileInfo]$File)
    return "$($File.LastWriteTimeUtc.Ticks):$($File.Length)"
}

Sync-All

if ($Once) {
    exit 0
}

Write-Host "Watching by polling every $IntervalSeconds second(s). Keep this window open. Press Ctrl+C to stop." -ForegroundColor Yellow
Write-Host "After sync, refresh browser with Ctrl+F5." -ForegroundColor Yellow

$known = @{}
Get-SyncableFiles | ForEach-Object {
    $known[$_.FullName] = Get-FileStampKey -File $_
}

while ($true) {
    Start-Sleep -Seconds $IntervalSeconds

    try {
        $files = @(Get-SyncableFiles)
        foreach ($file in $files) {
            $stamp = Get-FileStampKey -File $file
            if (-not $known.ContainsKey($file.FullName) -or $known[$file.FullName] -ne $stamp) {
                Copy-ChangedFile -FullPath $file.FullName
                $known[$file.FullName] = $stamp
            }
        }
    } catch {
        Write-Host "Watcher scan failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}
