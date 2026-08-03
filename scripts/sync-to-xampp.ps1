param(
    [string]$Source = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$Target = 'C:\xampp\htdocs\IAmStillHere',
    [switch]$Once
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
    '~$*'
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

    Get-ChildItem -LiteralPath $Source -Recurse -File -Force |
        Where-Object { -not (Test-IsExcludedPath -FullPath $_.FullName) } |
        ForEach-Object { Copy-ChangedFile -FullPath $_.FullName }

    Write-Host "Initial sync complete." -ForegroundColor Cyan
}

Sync-All

if ($Once) {
    exit 0
}

Write-Host "Watching for changes. Keep this window open. Press Ctrl+C to stop." -ForegroundColor Yellow

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $Source
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true

$pending = @{}

$action = {
    $path = $Event.SourceEventArgs.FullPath
    $changeType = $Event.SourceEventArgs.ChangeType

    if ($changeType -eq [System.IO.WatcherChangeTypes]::Deleted) {
        return
    }

    $script:pending[$path] = Get-Date
}

$subscriptions = @(
    Register-ObjectEvent $watcher Changed -Action $action
    Register-ObjectEvent $watcher Created -Action $action
    Register-ObjectEvent $watcher Renamed -Action $action
)

try {
    while ($true) {
        Start-Sleep -Milliseconds 500
        $now = Get-Date
        $ready = @($pending.Keys | Where-Object { ($now - $pending[$_]).TotalMilliseconds -gt 400 })

        foreach ($path in $ready) {
            $pending.Remove($path)
            try {
                Copy-ChangedFile -FullPath $path
            } catch {
                Write-Host "Sync failed: $path - $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
} finally {
    foreach ($subscription in $subscriptions) {
        Unregister-Event -SubscriptionId $subscription.Id -ErrorAction SilentlyContinue
    }
    $watcher.Dispose()
}
