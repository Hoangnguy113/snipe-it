@echo off
chcp 65001 >nul
cd /d "%~dp0"
title Phan mem quan ly thiet bi CNTT
echo ========================================================
echo   DANG KHOI DONG PHAN MEM QUAN LY THIET BI CNTT...
echo ========================================================

:: 1. Kiem tra va khoi dong MySQL noi bo
echo [1/3] Kiem tra Co so du lieu MySQL...
netstat -ano | findstr 127.0.0.1:3307 >nul
if %errorlevel% neq 0 (
    echo       Dang khoi dong MySQL daemon noi bo...
    start /b "" "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" --defaults-file="%~dp0tools\mysql_my.ini"
    timeout /t 3 /nobreak >nul
) else (
    echo       MySQL dang hoat dong san sang.
)

:: 2. Mo trinh duyet
echo [2/3] Mo trinh duyet den Snipe-IT...
start /b "" cmd /c "timeout /t 2 /nobreak >nul & start http://127.0.0.1:8000"

:: 3. Chay PHP Server
echo [3/3] Khoi dong Web Server (http://127.0.0.1:8000)...
echo ========================================================
echo   HE THONG DA SAN SANG TAI: http://127.0.0.1:8000
echo   Nhan Ctrl+C de dung server web hoac chay stop.bat
echo ========================================================
"%~dp0tools\php\php.exe" artisan serve --port=8000