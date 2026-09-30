# GĐ0 — Tác tử hợp nhất "Trợ lý CNTT" — Kế hoạch thực thi

> **Dành cho người thực thi:** BẮT BUỘC dùng sub-skill `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để làm từng việc một. Các bước dùng checkbox `- [ ]` để theo dõi.

**Mục tiêu:** Tạo 01 bộ cài duy nhất trên máy trạm, gói GLPI Agent 1.20 + RustDesk, chạy như Windows Service, báo cáo về Snipe-IT — và vá lỗi khiến agent không đọc được RustDesk ID.

**Kiến trúc:** GLPI Agent làm thân (QĐ-3). RustDesk do bộ cài chung đặt vào chế độ service. Cấu hình của ta đặt vào `etc/conf.d/qlts.cfg` — **không sửa `agent.cfg`**, vì `agent.cfg:170` đã có `include "conf.d/"`. Bộ cài đóng bằng Inno Setup, hiện đúng 1 dòng trong Programs & Features.

**Công nghệ:** Perl 5 (portable, đã kèm trong `glpi-agent-bin`) · Test::More / Test::MockModule · Inno Setup 6 · PowerShell 5.1 · RustDesk 1.4.9

**Spec:** [`../specs/2026-09-30-tich-hop-tac-tu-qlts-design.md`](../specs/2026-09-30-tich-hop-tac-tu-qlts-design.md)

**Kế hoạch tổng thể:** [`2026-09-30-tich-hop-tac-tu-qlts-tong-the.md`](2026-09-30-tich-hop-tac-tu-qlts-tong-the.md)

## Ràng buộc toàn dự án áp dụng cho GĐ0

| # | Ràng buộc |
|---|---|
| RB-6 | **`Task::Collect` luôn tắt.** `tasks = inventory,deploy` trong `etc/conf.d/qlts.cfg` |
| RB-12 | Ngưỡng: `delaytime` ứng với chu kỳ kiểm kê **24 giờ**, deploy hỏi việc **4 giờ** |
| — | **Sửa có chủ đích:** không sửa `etc/agent.cfg`, không sửa module nào khác của agent ngoài `RustDesk.pm` |

## ⚠️ Việc phải quyết trước khi bắt đầu

`D:\DEV\QLTS` **không phải git repo** (đã kiểm: `git log` không chạy). Các bước "Commit" dưới đây cần một repo. Đề nghị: `git init` ngay trong thư mục mới `D:\DEV\QLTS\tro-ly-cntt\`, và **không** đưa `glpi-agent-bin/` (nặng, nhị phân) vào repo đó — chỉ tham chiếu đường dẫn. Nếu chủ đầu tư muốn khác, dừng và hỏi.

## Sơ đồ file

| File | Trách nhiệm |
|---|---|
| `QLTS/glpi-agent/t/tasks/inventory/generic/remote_mgmt/rustdesk.t` | **Tạo** — test cho 3 hàm tách ra từ `RustDesk.pm` |
| `QLTS/glpi-agent/resources/generic/rustdesk/RustDesk-with-id.toml` | **Tạo** — fixture: config kiểu cũ có trường `id` |
| `QLTS/glpi-agent/resources/generic/rustdesk/RustDesk-enc-id.toml` | **Tạo** — fixture: config kiểu mới chỉ có `enc_id` |
| `QLTS/glpi-agent/lib/.../Remote_Mgmt/RustDesk.pm` | **Sửa** — tách `_getConfigPaths`, `_getExecutable`, `_supportsGetId`, `_getID`; `isEnabled` đa điều kiện |
| `QLTS/glpi-agent-bin/perl/agent/GLPI/.../Remote_Mgmt/RustDesk.pm` | **Sửa** — đồng bộ y hệt (đây là bản chạy thật) |
| `QLTS/glpi-agent-bin/etc/conf.d/qlts.cfg` | **Tạo** — cấu hình trỏ về Snipe-IT, bật inventory+deploy, tắt collect |
| `QLTS/tro-ly-cntt/installer/TroLyCNTT.iss` | **Tạo** — script Inno Setup |
| `QLTS/tro-ly-cntt/installer/rustdesk-postinstall.ps1` | **Tạo** — cài RustDesk service, đặt mật khẩu, bật xin chấp thuận |
| `QLTS/tro-ly-cntt/installer/uninstall.ps1` | **Tạo** — gỡ sạch 2 service |
| `QLTS/tro-ly-cntt/deploy/Install-TroLyCNTT.ps1` | **Tạo** — triển khai hàng loạt qua GPO/PsExec |
| `QLTS/tro-ly-cntt/README-trien-khai.md` | **Tạo** — hướng dẫn cho cán bộ CNTT |

---

## Task 1: Test đỏ cho RustDesk.pm

**Files:**
- Create: `D:\DEV\QLTS\glpi-agent\resources\generic\rustdesk\RustDesk-with-id.toml`
- Create: `D:\DEV\QLTS\glpi-agent\resources\generic\rustdesk\RustDesk-enc-id.toml`
- Create: `D:\DEV\QLTS\glpi-agent\t\tasks\inventory\generic\remote_mgmt\rustdesk.t`

**Interfaces:**
- Consumes: khuôn mẫu test của `t/tasks/inventory/generic/remote_mgmt/teamviewer.t`
- Produces: 3 hàm mà Task 2 phải hiện thực —
  - `_getConfigPaths(osname => $str)` → danh sách đường dẫn (list of strings)
  - `_supportsGetId(version => $str)` → `1` hoặc `0`
  - `_getID(files => \@paths, command => $str, osname => $str, logger => $obj)` → chuỗi ID hoặc `undef`

- [ ] **Bước 1: Tạo fixture config kiểu cũ (có trường `id`)**

Tạo `resources/generic/rustdesk/RustDesk-with-id.toml`:

```toml
id = '123456789'
password = 'khong-dung-den'
salt = 'khong-dung-den'
[options]
```

- [ ] **Bước 2: Tạo fixture config kiểu mới (chỉ có `enc_id`)**

Tạo `resources/generic/rustdesk/RustDesk-enc-id.toml`:

```toml
enc_id = '00OeMRGzMzIzNDU2Nzg5'
password = 'khong-dung-den'
salt = 'khong-dung-den'
[options]
```

- [ ] **Bước 3: Viết file test**

Tạo `t/tasks/inventory/generic/remote_mgmt/rustdesk.t`:

```perl
#!/usr/bin/perl

use strict;
use warnings;

use lib 't/lib';

use English qw(-no_match_vars);
use Test::More;
use Test::NoWarnings;

use GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk;

my %versions = (
    '1.4.9'         => 1,
    '1.3.0'         => 1,
    '1.2.2'         => 1,
    '1.2.1'         => 0,
    '1.1.9'         => 0,
    '0.9.9'         => 0,
    'khong-phai-so' => 0,
);

plan tests => scalar(keys %versions) + 6 + 1;

# --- _supportsGetId: cong ham --get-id chi co tu RustDesk 1.2.2 ---
foreach my $version (sort keys %versions) {
    is(
        GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_supportsGetId(
            version => $version
        ),
        $versions{$version},
        "_supportsGetId - $version"
    );
}

# --- _getConfigPaths: phai tra NHIEU duong dan, khong phai mot ---
my @win = GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_getConfigPaths(
    osname => 'MSWin32'
);
cmp_ok(scalar(@win), '>', 1, '_getConfigPaths MSWin32 tra ve nhieu duong dan');
ok(
    (grep { m{ServiceProfiles\\LocalService} } @win),
    '_getConfigPaths MSWin32 van giu duong dan LocalService'
);

my @nix = GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_getConfigPaths(
    osname => 'linux'
);
cmp_ok(scalar(@nix), '>', 1, '_getConfigPaths linux tra ve nhieu duong dan');

# --- _getID: doc duoc truong 'id' tu config kieu cu ---
# command tro tro toi file khong ton tai => canRun sai => bo qua nhanh --get-id
my $id = GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_getID(
    files   => [ 'resources/generic/rustdesk/RustDesk-with-id.toml' ],
    command => '/khong/ton/tai/rustdesk',
    osname  => 'linux',
);
is($id, '123456789', '_getID doc duoc truong id tu config kieu cu');

# --- _getID: config chi co enc_id thi KHONG duoc tra ve gi ---
my $encOnly = GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_getID(
    files   => [ 'resources/generic/rustdesk/RustDesk-enc-id.toml' ],
    command => '/khong/ton/tai/rustdesk',
    osname  => 'linux',
);
is($encOnly, undef, '_getID khong nham enc_id thanh id');

# --- _getID: khong co file nao va khong co binary => undef, khong no ---
my $nothing = GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk::_getID(
    files   => [ 'resources/generic/rustdesk/khong-co-file-nay.toml' ],
    command => '/khong/ton/tai/rustdesk',
    osname  => 'linux',
);
is($nothing, undef, '_getID tra undef khi khong co nguon nao');
```

- [ ] **Bước 4: Chạy test để xác nhận nó ĐỎ**

```bash
cd /d/DEV/QLTS/glpi-agent && perl -Ilib t/tasks/inventory/generic/remote_mgmt/rustdesk.t
```

Kỳ vọng: **THẤT BẠI** với thông báo kiểu `Undefined subroutine &...::_supportsGetId called`.

> Nếu máy không có Perl hệ thống, dùng Perl kèm trong bản đóng gói:
> `/d/DEV/QLTS/glpi-agent-bin/perl/bin/perl.exe -Ilib t/tasks/.../rustdesk.t`

- [ ] **Bước 5: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && git add -A && git commit -m "test: them test do cho RustDesk.pm (3 ham tach ra chua ton tai)"
```

---

## Task 2: Vá RustDesk.pm cho test xanh

**Files:**
- Modify: `D:\DEV\QLTS\glpi-agent\lib\GLPI\Agent\Task\Inventory\Generic\Remote_Mgmt\RustDesk.pm` (thay toàn bộ, 90 → ~120 dòng)
- Test: `t/tasks/inventory/generic/remote_mgmt/rustdesk.t`

**Interfaces:**
- Consumes: `GLPI::Agent::Tools` (`has_file`, `canRun`, `getFirstMatch`, `getFirstLine`, `empty`, `OSNAME`), `GLPI::Agent::Tools::Win32::getRegistryValue`
- Produces: `_getConfigPaths`, `_getExecutable`, `_supportsGetId`, `_getID`, `isEnabled`, `doInventory` — Task 3 sao y file này

- [ ] **Bước 1: Thay nội dung RustDesk.pm**

```perl
package GLPI::Agent::Task::Inventory::Generic::Remote_Mgmt::RustDesk;

# Based on the work done by Ilya published on no more existing https://fusioninventory.userecho.com site

use strict;
use warnings;

use parent 'GLPI::Agent::Task::Inventory::Module';

use English qw(-no_match_vars);

use GLPI::Agent::Tools;

# Every place a RustDesk.toml is known to live. The LocalService path is the
# one a service-mode install uses, but an agent running as a plain user cannot
# read it - which is why a single hardcoded path made isEnabled() always false
# and skipped doInventory() entirely.
sub _getConfigPaths {
    my (%params) = @_;

    my $osname = $params{osname} // OSNAME;

    if ($osname eq 'MSWin32') {
        my @paths = (
            'C:\Windows\ServiceProfiles\LocalService\AppData\Roaming\RustDesk\config\RustDesk.toml',
        );
        push @paths, $ENV{APPDATA}.'\RustDesk\config\RustDesk.toml'
            if $ENV{APPDATA};
        push @paths, $ENV{ProgramData}.'\RustDesk\config\RustDesk.toml'
            if $ENV{ProgramData};
        return @paths;
    }

    return (
        '/root/.config/rustdesk/RustDesk.toml',
        '/etc/rustdesk/RustDesk.toml',
    );
}

sub _getExecutable {
    my (%params) = @_;

    my $osname = $params{osname} // OSNAME;

    return 'rustdesk' unless $osname eq 'MSWin32';

    GLPI::Agent::Tools::Win32->require();
    my $installLocation = GLPI::Agent::Tools::Win32::getRegistryValue(
        path   => "HKEY_LOCAL_MACHINE/SOFTWARE/Microsoft/Windows/CurrentVersion/Uninstall/RustDesk/InstallLocation",
        logger => $params{logger}
    );

    return (empty($installLocation) ? 'C:\Program Files\RustDesk' : $installLocation)
        . '\rustdesk.exe';
}

# --get-id only exists since RustDesk 1.2.2. The version gate is kept on
# purpose: passing an unknown flag to an older GUI build can pop a window on
# the user's screen, which is unacceptable on a staff workstation.
sub _supportsGetId {
    my (%params) = @_;

    my $version = $params{version};
    unless (defined($version)) {
        return 0 unless $params{command};
        $version = getFirstLine(
            command => $params{command}." --version",
            logger  => $params{logger}
        );
    }

    return 0 unless $version && $version =~ /^(\d+)\.(\d+)\.(\d+)/;

    my ($major, $minor, $patch) = (int($1), int($2), int($3));

    return $major > 1                                       ? 1
         : $major == 1 && $minor > 2                         ? 1
         : $major == 1 && $minor == 2 && $patch >= 2         ? 1
         :                                                     0;
}

sub isEnabled {
    foreach my $path (_getConfigPaths()) {
        return 1 if has_file($path);
    }

    return canRun(_getExecutable()) ? 1 : 0;
}

# Ask the binary first: it is the only reliable source since RustDesk 1.3
# stores an encrypted enc_id instead of a plain id in the config file.
sub _getID {
    my (%params) = @_;

    my $osname  = $params{osname} // OSNAME;
    my $logger  = $params{logger};
    my $command = $params{command} // _getExecutable(
        osname => $osname,
        logger => $logger
    );

    if (canRun($command)) {
        my $quoted = $osname eq 'MSWin32' ? '"'.$command.'"' : $command;
        if (_supportsGetId(command => $quoted, logger => $logger)) {
            my $id = getFirstMatch(
                command => $quoted." --get-id",
                logger  => $logger,
                pattern => qr/^(\d+)$/
            );
            return $id if defined($id) && length($id);
            $logger->debug("RustDesk --get-id gave nothing, RustDesk may not be running")
                if $logger;
        } else {
            $logger->debug("RustDesk too old for --get-id, reading config file instead")
                if $logger;
        }
    }

    my @files = $params{files} ? @{$params{files}} : _getConfigPaths(osname => $osname);
    foreach my $file (@files) {
        next unless has_file($file);
        my $id = getFirstMatch(
            file    => $file,
            logger  => $logger,
            pattern => qr/^id\s*=\s*'?(\d+)'?\s*$/
        );
        return $id if defined($id) && length($id);
    }

    return;
}

sub doInventory {
    my (%params) = @_;

    my $inventory = $params{inventory};
    my $logger    = $params{logger};

    my $RustDeskID = _getID(logger => $logger);

    unless (defined($RustDeskID) && length($RustDeskID)) {
        $logger->debug('RustDesk ID not found') if $logger;
        return;
    }

    $logger->debug('Found RustDesk ID : ' . $RustDeskID) if $logger;

    $inventory->addEntry(
        section => 'REMOTE_MGMT',
        entry   => {
            ID   => $RustDeskID,
            TYPE => 'rustdesk'
        }
    );
}

1;
```

- [ ] **Bước 2: Chạy test để xác nhận XANH**

```bash
cd /d/DEV/QLTS/glpi-agent && perl -Ilib t/tasks/inventory/generic/remote_mgmt/rustdesk.t
```

Kỳ vọng: **tất cả 14 test PASS**, `Result: PASS`.

- [ ] **Bước 3: Chạy test biên dịch toàn bộ để chắc không làm vỡ module khác**

```bash
cd /d/DEV/QLTS/glpi-agent && perl -Ilib t/01compile.t 2>&1 | tail -5
```

Kỳ vọng: không có lỗi nào liên quan `RustDesk`.

- [ ] **Bước 4: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && git add -A && git commit -m "fix: RustDesk.pm doc duoc ID khi agent chay duoi user thuong"
```

---

## Task 3: Đồng bộ bản vá sang bản đóng gói + kiểm chứng trên máy thật

**Files:**
- Modify: `D:\DEV\QLTS\glpi-agent-bin\perl\agent\GLPI\Agent\Task\Inventory\Generic\Remote_Mgmt\RustDesk.pm`

**Interfaces:**
- Consumes: file đã vá ở Task 2
- Produces: bản đóng gói đọc được RustDesk ID — Task 5 gói thư mục này vào bộ cài

- [ ] **Bước 1: Sao lưu bản gốc**

```bash
cp "/d/DEV/QLTS/glpi-agent-bin/perl/agent/GLPI/Agent/Task/Inventory/Generic/Remote_Mgmt/RustDesk.pm" \
   "/d/DEV/QLTS/tro-ly-cntt/backup/RustDesk.pm.goc"
```

- [ ] **Bước 2: Sao bản đã vá sang bản đóng gói**

```bash
cp "/d/DEV/QLTS/glpi-agent/lib/GLPI/Agent/Task/Inventory/Generic/Remote_Mgmt/RustDesk.pm" \
   "/d/DEV/QLTS/glpi-agent-bin/perl/agent/GLPI/Agent/Task/Inventory/Generic/Remote_Mgmt/RustDesk.pm"
```

- [ ] **Bước 3: Kiểm chứng trên máy thật — agent phải đọc được ID**

```bash
cd /d/DEV/QLTS && ./glpi-agent-bin/glpi-agent.bat --local=- --debug 2>&1 | grep -i rustdesk
```

Kỳ vọng: thấy dòng `Found RustDesk ID : 16659046` (số ID của máy này).
**Nếu không thấy:** dừng lại, dùng skill `superpowers:systematic-debugging`. Không đi tiếp.

- [ ] **Bước 4: Kiểm chứng mục `REMOTE_MGMT` có trong bản kiểm kê**

```bash
cd /d/DEV/QLTS && ./glpi-agent-bin/glpi-agent.bat --local=- 2>/dev/null | grep -A3 -i "REMOTE_MGMT"
```

Kỳ vọng: thấy khối chứa `<ID>16659046</ID>` và `<TYPE>rustdesk</TYPE>`.

- [ ] **Bước 5: Lưu bản kiểm kê thật làm fixture cho GĐ1 và GĐ2**

```bash
mkdir -p "/d/DEV/Quanly-CNTT/tests/fixtures/inventory" && \
cd /d/DEV/QLTS && ./glpi-agent-bin/glpi-agent.bat --local="/d/DEV/Quanly-CNTT/tests/fixtures/inventory" --json
```

Kỳ vọng: sinh ra 1 file `.json` trong `Quanly-CNTT/tests/fixtures/inventory/`.
**Đây là fixture mà toàn bộ test của GĐ1 và GĐ2 sẽ dùng** — không được xoá.

- [ ] **Bước 6: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && git add -A && git commit -m "fix: dong bo ban va RustDesk.pm sang ban dong goi"
cd /d/DEV/Quanly-CNTT && git add tests/fixtures/inventory && git commit -m "test: them ban kiem ke that lam fixture"
```

---

## Task 4: Cấu hình agent trỏ về Snipe-IT

**Files:**
- Create: `D:\DEV\QLTS\glpi-agent-bin\etc\conf.d\qlts.cfg`

**Interfaces:**
- Consumes: cơ chế `include "conf.d/"` đã có sẵn ở `etc/agent.cfg:170`
- Produces: agent trỏ về endpoint của GĐ1; Task 5 gói file này (dạng template) vào bộ cài

- [ ] **Bước 1: Tạo file cấu hình**

Tạo `D:\DEV\QLTS\glpi-agent-bin\etc\conf.d\qlts.cfg`:

```ini
# Cau hinh Tro Ly CNTT - QLTS
# File nay duoc nap tu dong qua "include conf.d/" o agent.cfg:170
# KHONG SUA agent.cfg.

# Endpoint cua Snipe-IT (GD1). Doi thanh https:// truoc khi bat GD6.
server = http://127.0.0.1:8000/agent/inventory

# Chung thuc HTTP Basic. Agent gui lan dau KHONG kem chung thuc, may chu tra
# 401 + WWW-Authenticate: Basic realm="QLTS Agent", agent moi gui lai kem
# user/password nay (Client.pm:271-300). Day la CO CHE DUY NHAT agent ho tro -
# agent KHONG gui duoc header tuy y.
user = qlts-agent
password = THAY-BANG-MA-BI-MAT-THUC

# RB-6: CHI bat inventory va deploy. TUYET DOI khong bat collect
# (collect cho phep chay lenh tuy y duoi quyen SYSTEM - xem spec 12.1).
tasks = inventory,deploy

# RB-12: chu ky kiem ke 24 gio = 86400 giay
delaytime = 86400

# Nhan phong ban - bo cai se thay bang gia tri thuc
tag = CHUA-PHAN-NHOM

# Bat P2P de 300 may trao doi goi cai cho nhau, giam tai may chu
no-p2p = 0

# Ghi log ra file de truy su co tren may tram
logger = file
logfile = C:\ProgramData\TroLyCNTT\logs\agent.log
logfile-maxsize = 5
```

- [ ] **Bước 2: Kiểm chứng agent đọc được cấu hình**

```bash
cd /d/DEV/QLTS && ./glpi-agent-bin/glpi-agent.bat --config=none --conf-file=./glpi-agent-bin/etc/agent.cfg --debug --dump-config 2>&1 | grep -iE "^(server|tasks|delaytime|tag)"
```

Kỳ vọng: thấy `server` bằng URL Snipe-IT, `tasks` bằng `inventory,deploy` (**không có `collect`**), `delaytime = 86400`.

- [ ] **Bước 3: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && cp /d/DEV/QLTS/glpi-agent-bin/etc/conf.d/qlts.cfg installer/qlts.cfg.template && \
git add -A && git commit -m "feat: cau hinh agent tro ve Snipe-IT, tat task collect"
```

---

## Task 5: Bộ cài Inno Setup hợp nhất

**Files:**
- Create: `D:\DEV\QLTS\tro-ly-cntt\installer\TroLyCNTT.iss`
- Create: `D:\DEV\QLTS\tro-ly-cntt\installer\rustdesk-postinstall.ps1`
- Create: `D:\DEV\QLTS\tro-ly-cntt\installer\uninstall.ps1`

**Interfaces:**
- Consumes: `glpi-agent-bin/` đã vá (Task 3), `qlts.cfg.template` (Task 4), bộ cài RustDesk 1.4.9
- Produces: `TroLyCNTT-Setup.exe` — Task 6 gọi file này

> **Không TDD được bước này** (đóng gói nhị phân). Kiểm chứng bằng cài thật lên máy thử, các bước nêu rõ dưới đây.

- [ ] **Bước 1: Tạo script cài RustDesk**

Tạo `installer/rustdesk-postinstall.ps1`:

```powershell
# Cai RustDesk che do service + dat mat khau co dinh + BAT xin chap thuan.
param(
    [Parameter(Mandatory=$true)][string]$RustDeskSetup,
    [Parameter(Mandatory=$true)][string]$Password,
    [string]$RelayHost = ""
)
$ErrorActionPreference = "Stop"

# --silent-install dat RustDesk o che do service, chay duoi LocalService
Start-Process -FilePath $RustDeskSetup -ArgumentList "--silent-install" -Wait

$exe = Join-Path $env:ProgramFiles "RustDesk\rustdesk.exe"
if (-not (Test-Path $exe)) { throw "Khong tim thay $exe sau khi cai" }

# Mat khau co dinh cho truy cap khong nguoi truc
& $exe --password $Password

# Tro ve RustDesk Server noi bo neu co
if ($RelayHost -ne "") {
    & $exe --option custom-rendezvous-server $RelayHost
}

# Spec 12.5: nguoi dung PHAI biet minh dang bi dieu khien
& $exe --option approve-mode "password-click"
& $exe --option verification-method "use-permanent-password"

Write-Host "RustDesk da cai xong. ID = $(& $exe --get-id)"
```

- [ ] **Bước 2: Tạo script gỡ**

Tạo `installer/uninstall.ps1`:

```powershell
$ErrorActionPreference = "Continue"

# Dung va go service agent
& "$env:ProgramFiles\TroLyCNTT\glpi-agent-bin\glpi-agent.bat" --no-task all 2>$null
Stop-Service -Name "glpi-agent" -Force -ErrorAction SilentlyContinue
sc.exe delete "glpi-agent" | Out-Null

# Go RustDesk
$rd = Join-Path $env:ProgramFiles "RustDesk\rustdesk.exe"
if (Test-Path $rd) { Start-Process -FilePath $rd -ArgumentList "--uninstall" -Wait }

Remove-Item -Recurse -Force "$env:ProgramData\TroLyCNTT" -ErrorAction SilentlyContinue
Write-Host "Da go Tro Ly CNTT."
```

- [ ] **Bước 3: Tạo script Inno Setup**

Tạo `installer/TroLyCNTT.iss`:

```ini
#define AppName "Tro Ly CNTT"
#define AppVersion "1.0.0"
#define AgentSrc "..\..\glpi-agent-bin"
#define RustDeskSetup "rustdesk-1.4.9-x86_64.exe"

[Setup]
AppId={{8F2A6C41-1D7B-4E90-9A55-QLTS00000001}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher=Phong Cong nghe thong tin
DefaultDirName={autopf}\TroLyCNTT
DisableDirPage=yes
DisableProgramGroupPage=yes
PrivilegesRequired=admin
OutputBaseFilename=TroLyCNTT-Setup
Compression=lzma2/max
SolidCompression=yes
ArchitecturesInstallIn64BitMode=x64compatible
; Mot dong duy nhat trong Programs & Features
UninstallDisplayName={#AppName}
UninstallDisplayIcon={app}\glpi-agent-bin\glpi-agent.bat

[Files]
Source: "{#AgentSrc}\*"; DestDir: "{app}\glpi-agent-bin"; Flags: recursesubdirs createallsubdirs ignoreversion
Source: "qlts.cfg.template"; DestDir: "{app}"; Flags: ignoreversion
Source: "rustdesk-postinstall.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "uninstall.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "{#RustDeskSetup}"; DestDir: "{tmp}"; Flags: deleteafterinstall

[Dirs]
Name: "{commonappdata}\TroLyCNTT\logs"; Permissions: users-modify

[Code]
var
  PageThamSo: TInputQueryWizardPage;

procedure InitializeWizard;
begin
  PageThamSo := CreateInputQueryPage(wpSelectDir,
    'Tham so ket noi',
    'Nhap thong tin may chu quan ly',
    'Cac gia tri nay do Phong CNTT cung cap.');
  PageThamSo.Add('Dia chi may chu Snipe-IT:', False);
  PageThamSo.Add('Nhan phong ban (tag):', False);
  PageThamSo.Add('Mat khau RustDesk co dinh:', True);
  PageThamSo.Values[0] := 'http://127.0.0.1:8000/agent/inventory';
end;

function LayServer(Param: String): String;
begin Result := PageThamSo.Values[0]; end;

function LayTag(Param: String): String;
begin Result := PageThamSo.Values[1]; end;

function LayMatKhau(Param: String): String;
begin Result := PageThamSo.Values[2]; end;

[Run]
; 1. Dat cau hinh agent tu tham so nguoi cai nhap
Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -Command ""(Get-Content '{app}\qlts.cfg.template') -replace 'http://127.0.0.1:8000/agent/inventory','{code:LayServer}' -replace 'CHUA-PHAN-NHOM','{code:LayTag}' | Set-Content -Encoding UTF8 '{app}\glpi-agent-bin\etc\conf.d\qlts.cfg'"""; \
  StatusMsg: "Dang ghi cau hinh..."; Flags: runhidden

; 2. Cai RustDesk che do service
Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\rustdesk-postinstall.ps1"" -RustDeskSetup ""{tmp}\{#RustDeskSetup}"" -Password ""{code:LayMatKhau}"""; \
  StatusMsg: "Dang cai thanh phan dieu khien tu xa..."; Flags: runhidden

; 3. Dang ky agent thanh Windows Service (chay duoi SYSTEM)
Filename: "{app}\glpi-agent-bin\glpi-agent.bat"; \
  Parameters: "--register-service"; \
  StatusMsg: "Dang dang ky dich vu bao cao..."; Flags: runhidden

[UninstallRun]
Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\uninstall.ps1"""; \
  RunOnceId: "GoTroLyCNTT"; Flags: runhidden
```

> ⚠️ **Phải kiểm chứng ở Bước 5:** cờ `--register-service` của `glpi-agent.bat` và `--silent-install` của RustDesk 1.4.9 chưa được xác minh trên bản đóng gói này. Nếu cờ khác, sửa lại theo `glpi-agent.bat --help` và `rustdesk.exe --help`, rồi cập nhật file này.

- [ ] **Bước 4: Biên dịch bộ cài**

```bash
"/c/Program Files (x86)/Inno Setup 6/ISCC.exe" "D:\DEV\QLTS\tro-ly-cntt\installer\TroLyCNTT.iss"
```

Kỳ vọng: sinh ra `installer/Output/TroLyCNTT-Setup.exe`.

- [ ] **Bước 5: Cài thử lên 1 máy thử và kiểm 5 điều**

```powershell
# 1. Programs & Features chi co DUNG 1 dong
Get-ItemProperty HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*, HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\* |
  Where-Object { $_.DisplayName -match 'Tro Ly CNTT|RustDesk|GLPI' } |
  Select-Object DisplayName

# 2. Hai service dang chay
Get-Service | Where-Object { $_.Name -match 'glpi|rustdesk' } | Select-Object Name, Status, StartType

# 3. Agent chay duoi SYSTEM
Get-CimInstance Win32_Service -Filter "Name LIKE '%glpi%'" | Select-Object Name, StartName

# 4. Doc duoc RustDesk ID
& "$env:ProgramFiles\RustDesk\rustdesk.exe" --get-id

# 5. Cau hinh dung, KHONG co collect
Get-Content "$env:ProgramFiles\TroLyCNTT\glpi-agent-bin\etc\conf.d\qlts.cfg" | Select-String "server|tasks|tag"
```

Kỳ vọng: (1) **đúng 1 dòng** `Tro Ly CNTT` — không có dòng RustDesk hay GLPI riêng; (2) cả 2 service `Running`, `StartType = Automatic`; (3) `StartName = LocalSystem`; (4) in ra số ID; (5) `tasks = inventory,deploy`, **không có `collect`**.

- [ ] **Bước 6: Kiểm chứng gỡ sạch**

```powershell
# Go qua Programs & Features hoac:
& "$env:ProgramFiles\TroLyCNTT\unins000.exe" /SILENT
Start-Sleep -Seconds 20
Get-Service | Where-Object { $_.Name -match 'glpi|rustdesk' }
Test-Path "$env:ProgramFiles\TroLyCNTT"
Test-Path "$env:ProgramFiles\RustDesk"
```

Kỳ vọng: không còn service nào, cả 2 `Test-Path` trả `False`.

- [ ] **Bước 7: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && git add -A && git commit -m "feat: bo cai hop nhat Tro Ly CNTT (agent + rustdesk, 1 dong trong Programs and Features)"
```

---

## Task 6: Triển khai hàng loạt + tài liệu

**Files:**
- Create: `D:\DEV\QLTS\tro-ly-cntt\deploy\Install-TroLyCNTT.ps1`
- Create: `D:\DEV\QLTS\tro-ly-cntt\README-trien-khai.md`

**Interfaces:**
- Consumes: `TroLyCNTT-Setup.exe` (Task 5)
- Produces: quy trình cài cho 300+ máy

- [ ] **Bước 1: Viết script triển khai**

Tạo `deploy/Install-TroLyCNTT.ps1`:

```powershell
# Chay nhu GPO Startup Script (quyen may) hoac qua PsExec.
# Tu bo qua neu da cai roi, nen chay lai nhieu lan khong sao.
param(
    [Parameter(Mandatory=$true)][string]$SetupUnc,   # \\may-chu\phanmem\TroLyCNTT-Setup.exe
    [Parameter(Mandatory=$true)][string]$Server,     # https://may-chu/agent/inventory
    [Parameter(Mandatory=$true)][string]$Tag,        # ten phong ban
    [Parameter(Mandatory=$true)][string]$Password    # mat khau RustDesk co dinh
)
$ErrorActionPreference = "Stop"
$log = "$env:ProgramData\TroLyCNTT\trien-khai.log"
New-Item -ItemType Directory -Force -Path (Split-Path $log) | Out-Null

function Ghi($m) { "$(Get-Date -f 'yyyy-MM-dd HH:mm:ss')  $m" | Tee-Object -FilePath $log -Append }

if (Get-Service -Name "glpi-agent" -ErrorAction SilentlyContinue) {
    Ghi "Da cai san - bo qua."
    exit 0
}

$tmp = Join-Path $env:TEMP "TroLyCNTT-Setup.exe"
Ghi "Dang tai bo cai tu $SetupUnc"
Copy-Item -Path $SetupUnc -Destination $tmp -Force

Ghi "Dang cai im lang"
$p = Start-Process -FilePath $tmp -ArgumentList @(
    "/VERYSILENT", "/SUPPRESSMSGBOXES", "/NORESTART",
    "/SERVER=$Server", "/TAG=$Tag", "/RDPASS=$Password"
) -Wait -PassThru

if ($p.ExitCode -ne 0) { Ghi "LOI: ma thoat $($p.ExitCode)"; exit $p.ExitCode }

Ghi "Cai xong. Dang gui ban kiem ke dau tien."
& "$env:ProgramFiles\TroLyCNTT\glpi-agent-bin\glpi-agent.bat" --force
Ghi "Hoan tat."
```

> ⚠️ Bộ cài ở Task 5 hiện nhận tham số qua **trang wizard**, chưa nhận `/SERVER=` `/TAG=` `/RDPASS=` từ dòng lệnh. Bước 2 dưới đây bổ sung phần đó — bắt buộc, vì cài 300 máy không thể bấm wizard từng máy.

- [ ] **Bước 2: Cho bộ cài nhận tham số dòng lệnh**

Sửa `installer/TroLyCNTT.iss`, thêm vào mục `[Code]` (trước `InitializeWizard`):

```pascal
function LayThamSoDongLenh(Ten: String; MacDinh: String): String;
var
  i: Integer;
  s, tien_to: String;
begin
  Result := MacDinh;
  tien_to := '/' + Ten + '=';
  for i := 1 to ParamCount do begin
    s := ParamStr(i);
    if Pos(Uppercase(tien_to), Uppercase(s)) = 1 then begin
      Result := Copy(s, Length(tien_to) + 1, Length(s));
      Exit;
    end;
  end;
end;
```

Và sửa 3 hàm `Lay*` để ưu tiên tham số dòng lệnh:

```pascal
function LayServer(Param: String): String;
begin
  Result := LayThamSoDongLenh('SERVER', '');
  if Result = '' then Result := PageThamSo.Values[0];
end;

function LayTag(Param: String): String;
begin
  Result := LayThamSoDongLenh('TAG', '');
  if Result = '' then Result := PageThamSo.Values[1];
end;

function LayMatKhau(Param: String): String;
begin
  Result := LayThamSoDongLenh('RDPASS', '');
  if Result = '' then Result := PageThamSo.Values[2];
end;
```

- [ ] **Bước 3: Biên dịch lại và kiểm chứng cài im lặng có tham số**

```powershell
& "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" "D:\DEV\QLTS\tro-ly-cntt\installer\TroLyCNTT.iss"

# Cai im lang tren may thu, KHONG hien wizard
& "D:\DEV\QLTS\tro-ly-cntt\installer\Output\TroLyCNTT-Setup.exe" /VERYSILENT /SUPPRESSMSGBOXES /NORESTART `
    /SERVER=http://192.168.1.10:8000/agent/inventory /TAG=PHONG-KE-TOAN /RDPASS=MatKhauThu123

Start-Sleep -Seconds 30
Get-Content "$env:ProgramFiles\TroLyCNTT\glpi-agent-bin\etc\conf.d\qlts.cfg" | Select-String "server|tag"
```

Kỳ vọng: **không có cửa sổ wizard nào hiện ra**; file cấu hình chứa đúng `server = http://192.168.1.10:8000/agent/inventory` và `tag = PHONG-KE-TOAN`.

- [ ] **Bước 4: Viết tài liệu triển khai**

Tạo `README-trien-khai.md` gồm đủ 6 phần: (1) yêu cầu máy trạm; (2) cách cài 1 máy bằng wizard; (3) cách cài hàng loạt bằng GPO Startup Script, kèm ảnh chụp đường dẫn `Computer Configuration → Policies → Windows Settings → Scripts (Startup)`; (4) cách kiểm tra 1 máy đã cài đúng chưa (5 lệnh ở Task 5 Bước 5); (5) cách gỡ; (6) bảng xử lý 4 sự cố thường gặp: agent không gửi được (kiểm `server` + tường lửa), không đọc được RustDesk ID (kiểm service đang chạy), service không tự khởi động (kiểm `StartType`), log ở đâu (`C:\ProgramData\TroLyCNTT\logs\agent.log`).

- [ ] **Bước 5: Commit**

```bash
cd /d/DEV/QLTS/tro-ly-cntt && git add -A && git commit -m "feat: script trien khai hang loat + tai lieu huong dan"
```

---

## Cổng kiểm soát GĐ0 — phải dán được kết quả của các lệnh này

Theo `superpowers:verification-before-completion`, **không được** tuyên bố GĐ0 xong nếu chưa dán ra kết quả thật:

| # | Lệnh | Kỳ vọng |
|---|---|---|
| 1 | `perl -Ilib t/tasks/inventory/generic/remote_mgmt/rustdesk.t` | 14 test PASS |
| 2 | `./glpi-agent-bin/glpi-agent.bat --local=- --debug \| grep -i rustdesk` | `Found RustDesk ID : <số>` |
| 3 | `Get-ItemProperty HKLM:\...\Uninstall\* \| ? DisplayName -match 'Tro Ly CNTT\|RustDesk\|GLPI'` | **Đúng 1 dòng** |
| 4 | `Get-CimInstance Win32_Service -Filter "Name LIKE '%glpi%'" \| select StartName` | `LocalSystem` |
| 5 | `Get-Content ...\qlts.cfg \| Select-String tasks` | `inventory,deploy` — **không có `collect`** |
| 6 | Cài im lặng có `/SERVER= /TAG= /RDPASS=` | Không hiện wizard, cấu hình đúng |
| 7 | Gỡ rồi kiểm | Không còn service, 2 thư mục đã xoá |
| 8 | `ls /d/DEV/Quanly-CNTT/tests/fixtures/inventory/*.json` | Có ít nhất 1 file — fixture cho GĐ1/GĐ2 |
