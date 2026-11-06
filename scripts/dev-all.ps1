param(
    [ValidateSet('all', 'single')] [string] $Mode = 'all',
    [string] $Theme = '',
    # Project of this session (CMS_PROJECT_KEY). Empty = no fixed project: it follows the theme chosen in the administration.
    [string] $Project = '',
    # Print what would run (project, profile, config cache) and stop. Starts nothing.
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'

# Project -> public theme, the same map as Modules/CmsBackend/config/projects.php. Used only to refuse a mismatching command.
$projectThemes = @{
    'nam-binh-duong' = 'nam-binh-duong'
    'ban-de-an'      = 'ban-de-an'
    'khuyen-nong'    = 'default'
}

# Project and asset profile of this session (process environment only; .env and the database are not changed).
# The PHP server and Vite started below inherit it: all = every theme, single = shared assets + that theme.
if ([string]::IsNullOrWhiteSpace($Project)) {
    # start:all and the dev:* commands: no fixed project, it follows the theme selected in the administration.
    Remove-Item Env:CMS_PROJECT_KEY -ErrorAction SilentlyContinue
} else {
    if (-not $projectThemes.ContainsKey($Project)) {
        Write-Error "Du an khong hop le: '$Project'. Hop le: $($projectThemes.Keys -join ', ')."
    }
    if ($Mode -ne 'single' -or $Theme -ne $projectThemes[$Project]) {
        Write-Error "Du an '$Project' phai chay voi -Mode single -Theme $($projectThemes[$Project])."
    }
    $env:CMS_PROJECT_KEY = $Project
}
$env:CMS_ASSET_MODE = $Mode
if ($Mode -eq 'single') {
    if ([string]::IsNullOrWhiteSpace($Theme)) { Write-Error 'Mode single needs -Theme <key>.' }
    $env:CMS_ASSET_THEME = $Theme
} else {
    Remove-Item Env:CMS_ASSET_THEME -ErrorAction SilentlyContinue
}

$projectRoot = Split-Path -Parent $PSScriptRoot

$projectLabel = if ([string]::IsNullOrWhiteSpace($Project)) { 'theo theme dang chon trong quan tri' } else { $Project }

# A cached configuration freezes env(): the CMS_PROJECT_KEY / CMS_ASSET_* of an earlier run would stay in force and the
# variables set above would be ignored. Delete the cache file (only a cache, rebuilt on demand) before Laravel starts.
$configCache = Join-Path $projectRoot 'bootstrap\cache\config.php'
$hadConfigCache = Test-Path -LiteralPath $configCache

# .env is never edited, but a value written there applies when no process variable overrides it (start:all removes it).
$envFile = Join-Path $projectRoot '.env'
if ([string]::IsNullOrWhiteSpace($Project) -and (Test-Path -LiteralPath $envFile) -and
    (Select-String -LiteralPath $envFile -Pattern '^\s*CMS_PROJECT_KEY\s*=\s*\S' -Quiet)) {
    Write-Warning '.env dang co CMS_PROJECT_KEY: du an se bi co dinh theo .env, khong theo theme. Hay de trong gia tri do khi chay start:all.'
}

if ($DryRun) {
    Write-Host "DryRun - du an: $projectLabel | CMS_PROJECT_KEY=$env:CMS_PROJECT_KEY | CMS_ASSET_MODE=$env:CMS_ASSET_MODE | CMS_ASSET_THEME=$env:CMS_ASSET_THEME" -ForegroundColor Cyan
    Write-Host "DryRun - config cache: $(if ($hadConfigCache) { 'co, se chay php artisan config:clear' } else { 'khong co' }). Khong khoi dong gi." -ForegroundColor Cyan
    exit 0
}

$artisanPath = Join-Path $projectRoot 'artisan'
$publicPath = Join-Path $projectRoot 'public'
$serverRouter = Join-Path $projectRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$npmCommand = Get-Command npm.cmd -ErrorAction SilentlyContinue

if ($null -eq $phpCommand) {
    $wingetPackageRoot = Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages'
    $phpCommand = Get-ChildItem $wingetPackageRoot -Filter php.exe -File -Recurse -ErrorAction SilentlyContinue |
        Where-Object { $_.FullName -match 'PHP\.PHP\.8\.[23]_' } |
        Sort-Object FullName -Descending |
        Select-Object -First 1
}

if ($null -eq $phpCommand) {
    Write-Error 'Khong tim thay PHP. Hay cai PHP 8.2+ va mo lai terminal.'
}

$phpExecutable = $phpCommand.Source
if ([string]::IsNullOrWhiteSpace($phpExecutable)) {
    $phpExecutable = $phpCommand.FullName
}

if ($null -eq $npmCommand) {
    Write-Error 'Khong tim thay npm. Hay cai Node.js va mo lai terminal.'
}

if (-not (Test-Path -LiteralPath $artisanPath)) {
    Write-Error "Khong tim thay file artisan tai: $artisanPath"
}

if (-not (Test-Path -LiteralPath $serverRouter)) {
    Write-Error 'Khong tim thay Laravel server router. Hay chay composer install.'
}

$occupiedPort = Get-NetTCPConnection -LocalPort 8011 -State Listen -ErrorAction SilentlyContinue |
    Select-Object -First 1
if ($null -ne $occupiedPort) {
    $owner = Get-Process -Id $occupiedPort.OwningProcess -ErrorAction SilentlyContinue
    $ownerName = if ($null -ne $owner) { $owner.ProcessName } else { 'khong xac dinh' }
    Write-Error "Cong 8011 dang duoc su dung boi $ownerName (PID $($occupiedPort.OwningProcess))."
}

if ($hadConfigCache) {
    Write-Host 'Dang xoa config cache (bootstrap/cache/config.php) de bien moi truong cua lan chay nay co hieu luc...' -ForegroundColor Yellow
    & $phpExecutable $artisanPath config:clear
    if ($LASTEXITCODE -ne 0) { Write-Error 'php artisan config:clear that bai.' }
}

$phpProcess = $null
$exitCode = 0

try {
    Write-Host "Dang khoi dong Laravel: http://127.0.0.1:8011/ (website), http://127.0.0.1:8011/cms (quan tri) (du an: $projectLabel; giao dien: $Mode$(if ($Mode -eq 'single') { ' - ' + $Theme }))" -ForegroundColor Green
    $phpProcess = Start-Process `
        -FilePath $phpExecutable `
        -ArgumentList @('-S', '127.0.0.1:8011', $serverRouter) `
        -WorkingDirectory $publicPath `
        -NoNewWindow `
        -PassThru

    Start-Sleep -Milliseconds 700
    if ($phpProcess.HasExited) {
        throw "Laravel khong khoi dong duoc (exit code $($phpProcess.ExitCode))."
    }

    Write-Host 'Dang khoi dong Vite...' -ForegroundColor Green
    & $npmCommand.Source run dev
    $exitCode = $LASTEXITCODE
}
finally {
    if ($null -ne $phpProcess -and -not $phpProcess.HasExited) {
        Write-Host 'Dang tat Laravel server...' -ForegroundColor Yellow
        Stop-Process -Id $phpProcess.Id -Force -ErrorAction SilentlyContinue
        $phpProcess.WaitForExit()
    }
}

exit $exitCode
