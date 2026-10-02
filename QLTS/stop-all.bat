@echo off
chcp 65001 > nul
echo ========================================================
echo   DỪNG HỆ THỐNG QUẢN LÝ TÀI SẢN CÔNG (GLPI)
echo ========================================================
echo.
echo Đang tắt máy chủ GLPI và các dịch vụ trong WSL...
wsl -d Ubuntu -u root -- bash -c "service apache2 stop; service mariadb stop; service redis-server stop; pkill -f start_server.sh"
wsl --shutdown
echo.
echo Máy chủ và các dịch vụ đã được tắt hoàn toàn!
pause
