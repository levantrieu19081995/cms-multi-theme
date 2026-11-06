<#
  Deploy giao dien Khuyen nong len may 228.

  PHAM VI CO DINH:
    - Theme: default
    - Host: 172.16.1.228
    - Container: container-008-knct
    - Thu muc app: /domain/khuyennong

  Script chi deploy:
    - Blade view cua CmsFrontend/CmsBackend va resources/views
    - Hai lop trinh bay dung truc tiep boi giao dien: LocaleManager, AuthPageContext
    - lang/
    - public/assets/ (anh, font, tai nguyen tinh)
    - public/build/ cua theme default

  Mac dinh script KHONG chay migration. Khi co -RunMigrations, script sao luu
  database va chay toan bo migration Pending bang `php artisan migrate --force`.
  Script khong chay seeder, composer, queue worker; khong sua .env, upload,
  Nginx, domain, SSL hay container khac.

  Cach dung:
    npm run deploy:ui:kn
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts/deploy-ui-228.ps1
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts/deploy-ui-228.ps1 -DryRun
    powershell -NoProfile -ExecutionPolicy Bypass -File scripts/deploy-ui-228.ps1 -SkipBuild

  OpenSSH se hoi mat khau toi da hai lan (scp va ssh) neu may chua cau hinh SSH key.
#>
param(
    [string]$HostAddress = '172.16.1.228',
    [string]$SshUser = 'g2b',
    [string]$IdentityFile = '',
    [switch]$SkipBuild,
    [switch]$RunMigrations,
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$theme = 'default'
$container = 'container-008-knct'
$appPath = '/domain/khuyennong'
$domain = 'https://knct.cusc.vn'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$packageRoot = Join-Path $root "build-exports/ui-deploy/knct-$stamp"
$payloadRoot = Join-Path $packageRoot 'payload'
$archive = Join-Path $packageRoot "knct-ui-$stamp.tar.gz"
$buildDir = Join-Path $root "build-exports/themes/$theme/build"
$remoteArchive = "/tmp/knct-ui-$stamp.tar.gz"
$remoteRunner = "/tmp/apply-knct-ui-$stamp.sh"
$localRunner = Join-Path $packageRoot "apply-knct-ui-$stamp.sh"

function Step([string]$Text) {
    Write-Host "`n== $Text" -ForegroundColor Cyan
}

function Fail([string]$Text) {
    throw $Text
}

function Require-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        Fail "Khong tim thay lenh '$Name' trong PATH."
    }
}

function Copy-Tree([string]$RelativePath) {
    $source = Join-Path $root $RelativePath
    if (-not (Test-Path $source)) {
        Fail "Thieu nguon giao dien: $RelativePath"
    }

    $destination = Join-Path $payloadRoot $RelativePath
    $destinationParent = Split-Path -Parent $destination
    New-Item -ItemType Directory -Force -Path $destinationParent | Out-Null
    Copy-Item -Recurse -Force -Path $source -Destination $destinationParent
}

Require-Command node
Require-Command npm
Require-Command tar
Require-Command ssh
Require-Command scp

Step '1/7 Xac nhan dich deploy co dinh'
Write-Host "Theme     : $theme"
Write-Host "SSH       : $SshUser@$HostAddress"
Write-Host "Container : $container"
Write-Host "App       : $appPath"
Write-Host "Domain    : $domain"
Write-Host ("Database  : {0}" -f $(if ($RunMigrations) { 'SAO LUU truoc migration' } else { 'KHONG truy cap' }))
Write-Host ("Migration : {0}" -f $(if ($RunMigrations) { 'SAO LUU DB + php artisan migrate --force' } else { 'KHONG chay' }))
Write-Host 'Seeder    : KHONG chay'

Step '2/7 Kiem tra registry asset'
node scripts/theme-build.mjs $theme --check-only
if ($LASTEXITCODE -ne 0) { Fail 'Kiem tra registry asset khong dat.' }

if ($DryRun) {
    Write-Host "`nDryRun hoan tat. Chua build, dong goi, ket noi hay thay doi server." -ForegroundColor Yellow
    exit 0
}

Step '3/7 Build theme default'
if ($SkipBuild) {
    Write-Host 'Dung ban build hien co (-SkipBuild).'
} else {
    npm run build:default
    if ($LASTEXITCODE -ne 0) { Fail 'Build theme default that bai.' }
}

foreach ($required in @('manifest.json', 'theme-build.json', 'assets')) {
    if (-not (Test-Path (Join-Path $buildDir $required))) {
        Fail "Ban build thieu: $required"
    }
}

$metadata = Get-Content (Join-Path $buildDir 'theme-build.json') -Raw | ConvertFrom-Json
if ($metadata.mode -ne 'single' -or $metadata.themes -notcontains 'default') {
    Fail 'theme-build.json khong phai ban single theme default.'
}

if (Test-Path (Join-Path $root 'public/hot')) {
    Write-Host 'CANH BAO: local co public/hot; file nay khong duoc dong goi.' -ForegroundColor Yellow
}

Step '4/7 Dong goi dung pham vi giao dien'
New-Item -ItemType Directory -Force -Path $payloadRoot | Out-Null

foreach ($path in @(
    'app/Services/LocaleManager.php',
    'Modules/CmsFrontend/app/Support/AuthPageContext.php',
    'Modules/CmsFrontend/resources/views',
    'Modules/CmsBackend/resources/views',
    'resources/views',
    'lang',
    'public/assets'
)) {
    Copy-Tree $path
}

if ($RunMigrations) {
    Copy-Tree 'database/migrations'
    $deployTools = Join-Path $payloadRoot '.deploy'
    New-Item -ItemType Directory -Force -Path $deployTools | Out-Null
    Copy-Item -Force (Join-Path $PSScriptRoot 'deploy-db-config.php') (Join-Path $deployTools 'deploy-db-config.php')
}

$payloadBuild = Join-Path $payloadRoot 'public/build'
New-Item -ItemType Directory -Force -Path $payloadBuild | Out-Null
Copy-Item -Recurse -Force -Path (Join-Path $buildDir '*') -Destination $payloadBuild

if (Get-ChildItem $payloadRoot -Recurse -Force | Where-Object { $_.FullName -match '[\\/]public[\\/]hot$' }) {
    Fail 'Goi deploy khong hop le: co public/hot.'
}

tar -czf $archive -C $payloadRoot .
if ($LASTEXITCODE -ne 0 -or -not (Test-Path $archive)) {
    Fail 'Khong tao duoc goi tar.gz.'
}

$remoteScript = @'
set -euo pipefail

container='container-008-knct'
app='/domain/khuyennong'
archive='__REMOTE_ARCHIVE__'
stamp='__STAMP__'
stage="/tmp/knct-ui-$stamp"
backup="/domain/backups/ui-before-$stamp.tar.gz"
run_migrations='__RUN_MIGRATIONS__'

if ! lxc info "$container" >/dev/null 2>&1; then
  echo "Khong tim thay container $container" >&2
  exit 21
fi

actual_status=$(lxc list "$container" -c s --format csv)
if [ "$actual_status" != 'RUNNING' ]; then
  echo "Container $container khong RUNNING" >&2
  exit 22
fi

if ! lxc exec "$container" -- test -f "$app/artisan"; then
  echo "Sai thu muc app: $app" >&2
  exit 23
fi

lxc exec "$container" -- mkdir -p /domain/backups
lxc exec "$container" -- tar -czf "$backup" -C "$app" \
  app/Services/LocaleManager.php \
  Modules/CmsFrontend/app/Support/AuthPageContext.php \
  Modules/CmsFrontend/resources/views \
  Modules/CmsBackend/resources/views \
  resources/views lang public/assets public/build

lxc file push "$archive" "$container/tmp/knct-ui-$stamp.tar.gz"
lxc exec "$container" -- bash -lc "
  set -euo pipefail
  cd '$app'
  rm -rf '$stage'
  mkdir -p '$stage'
  tar -xzf '/tmp/knct-ui-$stamp.tar.gz' -C '$stage'

  test -f '$stage/public/build/manifest.json'
  test -f '$stage/public/build/theme-build.json'
  test ! -e '$stage/public/hot'

  if [ '$run_migrations' = 'true' ]; then
    test -d '$stage/database/migrations'
    test -f '$stage/.deploy/deploy-db-config.php'
    cp -a '$stage/database/migrations/.' 'database/migrations/'

    cnf='/tmp/knct-mysqldump-$stamp.cnf'
    dbfile='/tmp/knct-database-$stamp.txt'
    lastfile='/tmp/knct-last-migration-$stamp.txt'
    su -s /bin/bash www-data -c \"HOME=/tmp php '$stage/.deploy/deploy-db-config.php' '$app' '\$cnf' '\$dbfile' '\$lastfile'\"
    database=\$(cat \"\$dbfile\")
    last_migration=\$(cat \"\$lastfile\")
    test -n \"\$database\"
    if [ -z \"\$last_migration\" ]; then
      echo 'Khong xac dinh duoc migration moc; tu choi chay migrate --force.' >&2
      rm -f \"\$cnf\" \"\$dbfile\" \"\$lastfile\"
      exit 31
    fi
    mysqldump --defaults-extra-file=\"\$cnf\" --single-transaction --quick --routines --triggers --no-tablespaces \"\$database\" | gzip -1 > '/domain/backups/db-before-migrate-$stamp.sql.gz'
    gzip -t '/domain/backups/db-before-migrate-$stamp.sql.gz'
    rm -f \"\$cnf\" \"\$dbfile\" \"\$lastfile\"

    all_migrations='database/migrations.all-$stamp'
    backend_migrations='Modules/CmsBackend/database/migrations'
    backend_all='Modules/CmsBackend/database/migrations.all-$stamp'
    frontend_migrations='Modules/CmsFrontend/database/migrations'
    frontend_all='Modules/CmsFrontend/database/migrations.all-$stamp'
    mv database/migrations \"\$all_migrations\"
    mkdir -p database/migrations
    mv \"\$backend_migrations\" \"\$backend_all\"
    mkdir -p \"\$backend_migrations\"
    mv \"\$frontend_migrations\" \"\$frontend_all\"
    mkdir -p \"\$frontend_migrations\"
    restore_migrations() {
      rm -rf database/migrations
      mv \"\$all_migrations\" database/migrations
      rm -rf \"\$backend_migrations\"
      mv \"\$backend_all\" \"\$backend_migrations\"
      rm -rf \"\$frontend_migrations\"
      mv \"\$frontend_all\" \"\$frontend_migrations\"
    }
    trap restore_migrations EXIT

    selected=0
    for migration in '$stage'/database/migrations/*.php; do
      [ -f \"\$migration\" ] || continue
      name=\$(basename \"\$migration\" .php)
      if [[ \"\$name\" > \"\$last_migration\" ]]; then
        cp \"\$migration\" database/migrations/
        echo \"Migration moi: \$name\"
        selected=\$((selected + 1))
      fi
    done

    if [ \"\$selected\" -gt 0 ]; then
      su -s /bin/bash www-data -c 'php artisan migrate --force'
    else
      echo \"Khong co migration moi hon moc \$last_migration; bo qua migrate.\"
    fi

    trap - EXIT
    restore_migrations
    cp -a '$stage/database/migrations/.' database/migrations/
  fi

  cp -a '$stage/app/Services/LocaleManager.php' 'app/Services/LocaleManager.php'
  cp -a '$stage/Modules/CmsFrontend/app/Support/AuthPageContext.php' 'Modules/CmsFrontend/app/Support/AuthPageContext.php'
  cp -a '$stage/Modules/CmsFrontend/resources/views/.' 'Modules/CmsFrontend/resources/views/'
  cp -a '$stage/Modules/CmsBackend/resources/views/.' 'Modules/CmsBackend/resources/views/'
  cp -a '$stage/resources/views/.' 'resources/views/'
  cp -a '$stage/lang/.' 'lang/'
  cp -a '$stage/public/assets/.' 'public/assets/'

  rm -rf 'public/build.next-$stamp'
  mv '$stage/public/build' 'public/build.next-$stamp'
  if [ -d public/build ]; then mv public/build 'public/build.previous-$stamp'; fi
  mv 'public/build.next-$stamp' public/build
  rm -rf 'public/build.previous-$stamp'

  if [ -e public/hot ]; then
    mv public/hot '/domain/backups/public-hot-$stamp.txt'
  fi

  chown -R www-data:www-data \
    app/Services/LocaleManager.php \
    Modules/CmsFrontend/app/Support/AuthPageContext.php \
    Modules/CmsFrontend/resources/views \
    Modules/CmsBackend/resources/views \
    resources/views lang public/assets public/build \
    storage bootstrap/cache

  su -s /bin/bash www-data -c 'php artisan view:clear'

  rm -rf '$stage' '/tmp/knct-ui-$stamp.tar.gz'
"

rm -f "$archive" '__REMOTE_RUNNER__'
echo "BACKUP=$backup"
'@

$migrationFlag = if ($RunMigrations) { 'true' } else { 'false' }
$remoteScript = $remoteScript.Replace('__REMOTE_ARCHIVE__', $remoteArchive).Replace('__REMOTE_RUNNER__', $remoteRunner).Replace('__STAMP__', $stamp).Replace('__RUN_MIGRATIONS__', $migrationFlag).Replace("`r`n", "`n")
[System.IO.File]::WriteAllText($localRunner, $remoteScript, [System.Text.UTF8Encoding]::new($false))

Step '5/7 Tai goi giao dien len host 228'
$sshOptions = @('-o', 'StrictHostKeyChecking=yes')
if ($IdentityFile) {
    if (-not (Test-Path $IdentityFile)) { Fail "Khong tim thay SSH key: $IdentityFile" }
    $sshOptions += @('-i', $IdentityFile)
}

& scp @sshOptions $archive $localRunner "${SshUser}@${HostAddress}:/tmp/"
if ($LASTEXITCODE -ne 0) { Fail 'Tai goi len 228 that bai.' }

Step '6/7 Sao luu va cap nhat giao dien trong dung container'
& ssh @sshOptions "${SshUser}@${HostAddress}" "bash '$remoteRunner'"
if ($LASTEXITCODE -ne 0) {
    Fail 'Deploy tren 228 that bai. Kiem tra backup tren container truoc khi thu lai.'
}

Step '7/7 Kiem tra HTTP co ban'
$checks = @('/', '/cms/login')
foreach ($path in $checks) {
    try {
        $response = Invoke-WebRequest -Uri ($domain + $path) -Method Head -UseBasicParsing -MaximumRedirection 5 -TimeoutSec 30
        Write-Host ("{0} -> HTTP {1}" -f $path, [int]$response.StatusCode)
    } catch {
        Fail "Kiem tra $path that bai: $($_.Exception.Message)"
    }
}

Write-Host "`nDeploy giao dien Khuyen nong hoan tat." -ForegroundColor Green
if ($RunMigrations) {
    Write-Host 'Da sao luu database va chay: php artisan migrate --force' -ForegroundColor Yellow
} else {
    Write-Host 'Khong chay migration, seeder, composer va khong truy cap database.'
}
Write-Host "Goi local: $archive"

