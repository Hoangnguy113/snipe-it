# HỆ THỐNG QUẢN LÝ TÀI SẢN CÔNG & HẠ TẦNG CNTT (GLPI & GLPI AGENT)

Dự án đã được cài đặt hoàn tất, vận hành ổn định và **Việt hoá 100%** theo đúng chuẩn thuật ngữ **Quản lý tài sản công** (Luật Quản lý, sử dụng tài sản công, Thông tư hướng dẫn quản lý, tính hao mòn, khấu hao tài sản cố định) và **Hạ tầng CNTT (ITSM/ITIL)**.

---

## 1. Thông Tin Truy Cập Nhanh

* **Đường dẫn Web GLPI:** [http://localhost:8080/](http://localhost:8080/)
* **Cơ sở dữ liệu:** MariaDB (Database: `glpi`, User: `glpi`)
* **Tài khoản mặc định:**
  * **Quản trị viên tối cao (Super-Admin):** `glpi` / `glpi`
  * **Quản trị viên kỹ thuật (Admin):** `tech` / `tech`
  * **Cán bộ quản lý thông thường (Normal):** `normal` / `normal`
  * **Tài khoản chỉ gửi phiếu yêu cầu (Post-only):** `post-only` / `postonly`

*(Khuyến nghị: Đổi mật khẩu mặc định sau lần đăng nhập đầu tiên).*

---

## 2. Các Tập Tin Thực Thi Nhanh (1-Click)

Tại thư mục `D:\DEV\QLTS`:
* `start-all.bat`: Khởi động MariaDB, Redis, Apache và mở trình duyệt (giữ cửa sổ mở/thu nhỏ để duy trì máy chủ).
* `start-chay-ngam.vbs`: Khởi động máy chủ chạy ngầm hoàn toàn (không hiện cửa sổ đen).
* `stop-all.bat`: Tắt máy chủ và các dịch vụ khi không còn sử dụng.
* `run-agent-inventory.bat`: Chạy tác tử GLPI Agent quét toàn bộ cấu hình máy tính hiện tại và đẩy dữ liệu lên máy chủ GLPI.
* `translate_glpi.py`: Script tự động dịch và biên dịch tập tin ngôn ngữ tiếng Việt (`.po` và `.mo`).

---

## 3. Kết Quả Việt Hoá 100% Theo Chuẩn Quản Lý Tài Sản Công & CNTT

### A. Máy chủ GLPI (`glpi`):
* **Tổng số chuỗi:** 6.860 / 6.860 chuỗi (**100% hoàn thành**, 0 chuỗi chưa dịch, 0 chuỗi nghi ngờ/fuzzy).
* **Tập tin ngôn ngữ:**
  * `D:\DEV\QLTS\glpi\locales\vi_VN.po`
  * `D:\DEV\QLTS\glpi\locales\vi_VN.mo` (đã biên dịch nhị phân bằng GNU `msgfmt`).

### B. Tác tử GLPI Agent (`glpi-agent`):
Đã tạo toàn bộ 10 tập tin từ điển tiếng Việt cho giao diện quản trị Agent ToolBox:
* `common-language-vi.txt`: Thuật ngữ chung
* `configuration-language-vi.txt`: Cấu hình tác tử
* `credentials-language-vi.txt`: Thông tin xác thực truy cập SNMP / SSH / WinRM
* `errors-language-vi.txt`: Thông báo lỗi hệ thống
* `infos-language-vi.txt`: Thông tin tác tử
* `inventory-language-vi.txt`: Kiểm kê phần cứng, phần mềm, thiết bị mạng
* `ip_range-language-vi.txt`: Quản lý dải địa chỉ IP dò quét
* `mibsupport-language-vi.txt`: Hỗ trợ MIB SNMP thiết bị mạng
* `results-language-vi.txt`: Bảng kết quả kiểm kê tài sản
* `scheduling-language-vi.txt`: Lập lịch kiểm kê định kỳ

---

## 4. Chuẩn Thuật Ngữ Nghiệp Vụ Đã Chuẩn Hoá

| Thuật ngữ gốc tiếng Anh | Thuật ngữ chuẩn Quản lý tài sản công & CNTT |
| :--- | :--- |
| **Assets** | **Tài sản công / Danh mục tài sản** |
| **Computers** | **Máy vi tính (Desktop / Laptop)** |
| **Monitors** | **Màn hình hiển thị** |
| **Printers** | **Máy in văn phòng** |
| **Network devices** | **Thiết bị mạng (Switch, Router, Firewall)** |
| **Cartridges / Consumables** | **Hộp mực máy in / Vật tư tiêu hao** |
| **Peripherals / Devices** | **Thiết bị ngoại vi** |
| **Racks / Enclosures / PDUs** | **Tủ rack / Khung máy / Bộ phân phối nguồn PDU** |
| **Financial and administrative info** | **Thông tin tài chính & Quản lý tài sản công** |
| **Value / Purchase price** | **Nguyên giá tài sản** |
| **Residual value** | **Giá trị còn lại của tài sản** |
| **Depreciation / Amortization** | **Hao mòn / Khấu hao tài sản cố định** |
| **Commissioning date** | **Ngày đưa vào sử dụng** |
| **Decommissioning date** | **Ngày dừng sử dụng / Ngày thanh lý** |
| **Transfer** | **Điều chuyển tài sản (giữa các cơ quan, đơn vị)** |
| **Drop / Purge / Trash** | **Thanh lý tài sản / Tiêu hủy / Chờ thanh lý** |
| **Inventory** | **Kiểm kê tài sản** |
| **Entity / Entities** | **Cơ quan / Đơn vị / Phòng ban quản lý** |
| **Supplier** | **Nhà cung cấp / Đơn vị trúng thầu** |
| **Contract** | **Hợp đồng kinh tế / Hợp đồng mua sắm, bảo trì** |
| **Ticket / Assistance** | **Phiếu yêu cầu / Hỗ trợ kỹ thuật CNTT** |
| **Incident / Problem / Change** | **Sự cố kỹ thuật / Vấn đề / Yêu cầu thay đổi** |
| **GLPI Agent** | **Tác tử thu thập thông tin tự động** |

---

## 5. Kiểm Tra Tự Động Thành Công

1. **Khởi tạo Database & Web:** Đã cài đặt schema cơ sở dữ liệu `glpi` trên MariaDB 11.8. Apache Web Server đang phục vụ tại cổng `8080`.
2. **Xác thực Đăng nhập:** Đã kiểm tra đăng nhập thành công tài khoản `glpi` vào giao diện chính thức.
3. **Kiểm kê thực tế:** Đã chạy thử nghiệm GLPI Agent trên chính máy trạm (Admin-PC, bo mạch chủ MSI PRO H610M-E, 32GB RAM). Toàn bộ cấu hình phần cứng, linh kiện, CPU, bộ nhớ, card mạng và hệ điều hành đã được tự động nhập vào cơ sở dữ liệu quản lý tài sản của GLPI.
