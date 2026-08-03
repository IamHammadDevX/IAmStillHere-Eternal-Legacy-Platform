$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Resolve-Path (Join-Path $scriptDir '..')
$watcher = Join-Path $scriptDir 'sync-to-xampp.ps1'
$pidFile = Join-Path $scriptDir 'xampp-sync.pid'
$logFile = Join-Path $scriptDir 'xampp-sync.log'

if (Test-Path -LiteralPath $pidFile) {
    $existingPid = Get-Content -LiteralPath $pidFile -ErrorAction SilentlyContinue
    if ($existingPid) {
        $existing = Get-Process -Id ([int]$existingPid) -ErrorAction SilentlyContinue
        if ($existing) {
            Write-Host "XAMPP sync already running. PID: $existingPid" -ForegroundColor Yellow
            exit 0
        }
    }
}

$command = "& '$watcher' *> '$logFile'"
$process = Start-Process powershell.exe `
    -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', $command) `
    -WorkingDirectory $repoRoot `
    -WindowStyle Hidden `
    -PassThru

Set-Content -LiteralPath $pidFile -Value $process.Id -Encoding ASCII
Write-Host "Started XAMPP sync watcher. PID: $($process.Id)" -ForegroundColor Green
Write-Host "Log: $logFile"
