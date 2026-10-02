@echo off
chcp 65001 > nul
echo ========================================================
echo   CHẠY TÁC TỬ KIỂM KÊ TÀI SẢN TỰ ĐỘNG (GLPI AGENT)
echo ========================================================
echo.
echo Đang quét phần cứng, phần mềm và cấu hình máy vi tính...
echo Gửi dữ liệu kiểm kê về máy chủ GLPI tại http://localhost:8080/front/inventory.php ...
echo.

call "%~dp0glpi-agent-bin\glpi-agent.bat" --server=http://localhost:8080/front/inventory.php

echo.
echo ========================================================
echo   KIỂM KÊ HOÀN TẤT!
echo   Dữ liệu đã được cập nhật vào danh mục Tài sản của GLPI.
echo ========================================================
pause
