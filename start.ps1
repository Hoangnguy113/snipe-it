$rootDir = $PSScriptRoot
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "   DANG KHOI DONG PHAN MEM QUAN LY THIET BI CNTT..." -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Cyan

# 1. Kiem tra va khoi dong MySQL noi bo
Write-Host "[1/3] Kiem tra Co so du lieu MySQL..." -ForegroundColor Yellow
$mysqlPort = Get-NetTCPConnection -LocalPort 3307 -ErrorAction SilentlyContinue
if (-not $mysqlPort) {
    Write-Host "      Dang khoi dong MySQL daemon noi bo..." -ForegroundColor Gray
    Start-Process -FilePath "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" -ArgumentList "--defaults-file=$rootDir\tools\mysql_my.ini" -WindowStyle Hidden
    Start-Sleep -Seconds 3
} else {
    Write-Host "      MySQL dang hoat dong san sang." -ForegroundColor Green
}

# 2. Mo trinh duyet
Write-Host "[2/3] Mo trinh duyet den Snipe-IT..." -ForegroundColor Yellow
Start-Job -ScriptBlock { Start-Sleep -Seconds 2; Start-Process "http://127.0.0.1:8000" } | Out-Null

# 3. Chay PHP Built-in Server
Write-Host "[3/3] Khoi dong Web Server..." -ForegroundColor Yellow
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "   HE THONG DA SAN SANG TAI: http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "   Nhan Ctrl+C hoac chay .\stop.ps1 de dung server" -ForegroundColor Gray
Write-Host "========================================================" -ForegroundColor Cyan
& "$rootDir\tools\php\php.exe" "$rootDir\artisan" serve --port=8000