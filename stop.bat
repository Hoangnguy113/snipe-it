@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo ========================================================
echo   DANG DUNG HE THONG SNIPE-IT...
echo ========================================================

:: Dung tien trinh php tren port 8000
for /f "tokens=5" %%a in ('netstat -ano ^| findstr 127.0.0.1:8000') do (
    taskkill /f /pid %%a >nul 2>&1
)

:: Dung tien trinh mysqld
echo Dang dung MySQL daemon noi bo...
"C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqladmin.exe" -P 3307 -u root shutdown >nul 2>&1
taskkill /f /im mysqld.exe >nul 2>&1

echo Da dung toan bo he thong.
pause