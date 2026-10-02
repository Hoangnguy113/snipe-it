$rootDir = $PSScriptRoot
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "   DANG DUNG HE THONG SNIPE-IT..." -ForegroundColor Yellow
Write-Host "========================================================" -ForegroundColor Cyan

# Dung PHP server
$phpConn = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
if ($phpConn) {
    $pids = $phpConn | Select-Object -ExpandProperty OwningProcess -Unique
    foreach ($p in $pids) {
        Stop-Process -Id $p -Force -ErrorAction SilentlyContinue
    }
    Write-Host "Da dung web server tren cong 8000." -ForegroundColor Green
}

# Dung MySQL
& "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqladmin.exe" -P 3307 -u root shutdown 2>$null
Stop-Process -Name mysqld -Force -ErrorAction SilentlyContinue
Write-Host "Da dung MySQL daemon noi bo." -ForegroundColor Green
Write-Host "Da dung toan bo he thong thanh cong." -ForegroundColor Green