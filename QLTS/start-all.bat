@echo off
chcp 65001 > nul
title HỆ THỐNG QUẢN LÝ TÀI SẢN CÔNG & CNTT (GLPI)
echo ========================================================
echo   KHỞI ĐỘNG HỆ THỐNG QUẢN LÝ TÀI SẢN CÔNG & CNTT (GLPI)
echo ========================================================
echo.
echo [1/3] Đang khởi động các dịch vụ (Redis, MariaDB, Apache)...
wsl -d Ubuntu -u root -- bash -c "mkdir -p /var/cache/glpi /var/lib/glpi_sessions && chmod -R 777 /var/cache/glpi /var/lib/glpi_sessions && chown -R www-data:www-data /var/cache/glpi /var/lib/glpi_sessions && service redis-server start && service mariadb start && service apache2 start"

echo.
echo [2/3] Kiểm tra trạng thái máy chủ...
wsl -d Ubuntu -u root -- bash -c "service redis-server status | grep Active; service mariadb status | grep Active; service apache2 status | grep Active"

echo.
echo [3/3] Đang mở giao diện GLPI trên trình duyệt...
start http://localhost:8080/

echo.
echo ========================================================
echo   HỆ THỐNG ĐÃ SẴN SÀNG VÀ ĐANG HOẠT ĐỘNG!
echo   - URL truy cập: http://localhost:8080/
echo   - Tài khoản quản trị tối cao (Super-Admin): glpi / glpi
echo   - Tài khoản quản trị kỹ thuật (Admin): tech / tech
echo   - Tài khoản cán bộ quản lý (Normal): normal / normal
echo   - Tài khoản gửi phiếu (Post-only): post-only / postonly
echo ========================================================
echo.
echo [LƯU Ý]: Giữ cửa sổ này mở để máy chủ duy trì hoạt động.
echo (Bạn có thể thu nhỏ cửa sổ này xuống thanh Taskbar).
echo Để dừng máy chủ, bạn chỉ cần đóng cửa sổ này lại hoặc chạy file stop-all.bat.
echo.

wsl -d Ubuntu -u root -- /mnt/d/DEV/QLTS/start_server.sh
