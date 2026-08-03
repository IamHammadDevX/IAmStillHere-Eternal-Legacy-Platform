$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$pidFile = Join-Path $scriptDir 'xampp-sync.pid'

if (-not (Test-Path -LiteralPath $pidFile)) {
    Write-Host 'XAMPP sync watcher is not running.' -ForegroundColor Yellow
    exit 0
}

$pidValue = Get-Content -LiteralPath $pidFile
$process = Get-Process -Id ([int]$pidValue) -ErrorAction SilentlyContinue

if ($process) {
    Stop-Process -Id $process.Id -Force
    Write-Host "Stopped XAMPP sync watcher. PID: $pidValue" -ForegroundColor Green
} else {
    Write-Host 'Saved PID was not running.' -ForegroundColor Yellow
}

Remove-Item -LiteralPath $pidFile -Force -ErrorAction SilentlyContinue
