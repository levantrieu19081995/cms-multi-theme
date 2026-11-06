<#
  Đóng gói bản deploy của MỘT theme (mặc định: default = Khuyến nông, knct.cusc.vn). Chỉ tạo thư mục/zip trong build-exports/deploy; không đụng server, không đụng database.

  Cách dùng (từ thư mục gốc dự án):
    powershell -File scripts/deploy-package.ps1                       # kiểm tra + build + đóng gói theme default
    powershell -File scripts/deploy-package.ps1 -Theme nam-binh-duong
    powershell -File scripts/deploy-package.ps1 -DryRun               # chỉ kiểm tra và liệt kê, không build, không copy
    powershell -File scripts/deploy-package.ps1 -SkipBuild            # dùng build-exports/themes/<theme>/build đã có

  Gói gồm: code (toàn bộ, không kèm .env, vendor, node_modules, storage, tests, bản build cũ), thư mục build/ của theme, DEPLOY-STEPS.txt.
  KHÔNG kèm .env: mỗi server có .env riêng (nhớ thêm DEVELOPER_ACCESS_TOKEN_HASH, xem DEPLOY-STEPS.txt).
#>
param(
    [ValidateSet('default', 'nam-binh-duong', 'ban-de-an')][string]$Theme = 'default',
    [switch]$DryRun,
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$out = Join-Path $root "build-exports/deploy/$Theme-$stamp"
$buildDir = Join-Path $root "build-exports/themes/$Theme/build"

function Step($text) { Write-Host "`n== $text" -ForegroundColor Cyan }
function Fail($text) { Write-Host "LỖI: $text" -ForegroundColor Red; exit 1 }

Step "1/5 Kiểm tra registry asset của theme '$Theme' (không build)"
node scripts/theme-build.mjs $Theme --check-only
if ($LASTEXITCODE -ne 0) { Fail 'theme-build --check-only không đạt.' }

Step '2/5 Kiểm tra cú pháp PHP của các tệp trang chủ/Developer'
$php = Get-ChildItem Modules/CmsFrontend/app, Modules/CmsBackend/app, app, routes, lang -Recurse -Filter *.php | Select-Object -ExpandProperty FullName
$bad = 0
foreach ($file in $php) {
    $result = php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Host $result; $bad++ }
}
if ($bad -gt 0) { Fail "$bad tệp PHP lỗi cú pháp." }
Write-Host ('Đã kiểm {0} tệp PHP, không lỗi.' -f $php.Count)

Step '3/5 Migration chưa chạy (chỉ liệt kê tên tệp trong database/migrations từ 2026_11_01)'
Get-ChildItem database/migrations -Filter '2026_11_*.php' | ForEach-Object { Write-Host ('  ' + $_.Name) }

if ($DryRun) { Write-Host "`nDryRun: dừng tại đây, chưa build/copy gì." -ForegroundColor Yellow; exit 0 }

Step "4/5 Build asset của theme '$Theme'"
if ($SkipBuild) {
    Write-Host 'Bỏ qua build (-SkipBuild).'
} else {
    npm run build:theme -- $Theme
    if ($LASTEXITCODE -ne 0) { Fail 'npm run build:theme lỗi.' }
}
if (-not (Test-Path (Join-Path $buildDir 'manifest.json'))) { Fail "Thiếu $buildDir/manifest.json (chưa build?)." }
if (-not (Test-Path (Join-Path $buildDir 'theme-build.json'))) { Fail "Thiếu $buildDir/theme-build.json." }

Step "5/5 Đóng gói vào $out"
New-Item -ItemType Directory -Force -Path "$out/code", "$out/build" | Out-Null
$skipDirs = @(
    'node_modules',
    'vendor',
    'storage',
    '.git',
    '.codex',
    '.codex-backup',
    'tests',
    'build-exports',
    (Join-Path $root 'public/build'),
    (Join-Path $root 'bootstrap/cache')
)
robocopy $root "$out/code" /E /NFL /NDL /NJH /NJS /NP /XD $skipDirs /XF '.env' '.env.local' '.env.production' '.env.backup' 'public/hot' 'hot' '*.log' | Out-Null
if ($LASTEXITCODE -ge 8) { Fail "robocopy lỗi (mã $LASTEXITCODE)." }
Copy-Item -Recurse -Force "$buildDir/*" "$out/build"
@"
CÁC BƯỚC TRÊN SERVER (theme: $Theme, gói tạo lúc $stamp)

0. Sao lưu: thư mục dự án (trừ vendor/storage) và database. Sao lưu public/build đang chạy.
1. Đưa nội dung code/ đè lên thư mục dự án trên server. KHÔNG đè .env, storage/, vendor/.
2. Xóa public/build cũ, chép TOÀN BỘ nội dung build/ vào public/build (manifest.json, theme-build.json, assets/). Không trộn file của hai bản build.
3. .env của server: thêm DEVELOPER_ACCESS_TOKEN_HASH='<mã bcrypt>' (đặt trong dấu nháy đơn). Tạo mã:
     php -r "echo password_hash('MẬT_KHẨU_DEVELOPER', PASSWORD_BCRYPT);"
   Giữ nguyên các khóa khác (APP_KEY, DB_*, CMS_*).
4. Nếu composer.json/composer.lock đổi: composer install --no-dev --optimize-autoloader
5. Migration. Cơ sở dữ liệu được nhập từ nguồn có sẵn nên các migration cũ (2024-2025) hiện là Pending dù bảng đã có. Làm một lần:
     php artisan migrate:baseline            (chạy thử: chỉ in danh sách, không ghi gì; đọc kỹ danh sách)
     php artisan migrate:baseline --force    (đánh dấu các migration cũ là đã chạy; KHÔNG chạy migration, KHÔNG đổi bảng dữ liệu)
   Sau đó migrate thường chỉ còn chạy các migration mới (sao lưu database trước):
     php artisan migrate --force
   Máy chủ MỚI (database trống): xem docs/DEPLOY-CHECKLIST.md mục "Máy chủ mới".
6. Cài bố cục trang chủ lần đầu (một lần cho mỗi theme dùng trên server):
     php artisan homepage:layout-install $Theme
7. Xóa cache:
     php artisan optimize:clear
     php artisan config:cache
     php artisan route:cache      (bỏ qua nếu server không dùng route cache)
     php artisan storage:link     (chỉ khi public/storage chưa có)
8. Quyền ghi: storage/ và bootstrap/cache/ phải ghi được bởi user chạy web.
9. Kiểm tra: mở trang chủ, /cms/login, /cms/developer-settings (cần mật khẩu Developer).
10. Lùi lại khi có sự cố: trả lại bản sao lưu bước 0 (code + public/build), php artisan optimize:clear.
"@ | Set-Content -Encoding utf8 "$out/DEPLOY-STEPS.txt"

Compress-Archive -Path "$out/*" -DestinationPath "$out.zip" -Force
Write-Host "`nXong. Gói: $out.zip" -ForegroundColor Green
Write-Host "Đọc $out/DEPLOY-STEPS.txt để chạy các bước trên server."
