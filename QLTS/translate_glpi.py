# -*- coding: utf-8 -*-
"""
Script dịch và việt hoá 100% GLPI theo chuẩn Quản lý tài sản công & CNTT
"""
import os
import re
import sys
import json
import time
import urllib.request
import urllib.parse
import polib

sys.stdout.reconfigure(encoding='utf-8')

PO_FILE = r'd:\DEV\QLTS\glpi\locales\vi_VN.po'
MO_FILE = r'd:\DEV\QLTS\glpi\locales\vi_VN.mo'
CACHE_FILE = r'd:\DEV\QLTS\trans_cache.json'

# Từ điển chuyên ngành Quản lý tài sản công & CNTT (Public Asset Management & IT)
GLOSSARY = {
    # Tài sản & Danh mục
    "Assets": "Tài sản công",
    "Asset": "Tài sản",
    "Fixed asset": "Tài sản cố định",
    "Fixed assets": "Tài sản cố định",
    "Consumable": "Vật tư tiêu hao",
    "Consumables": "Vật tư tiêu hao",
    "Cartridge": "Hộp mực máy in",
    "Cartridges": "Hộp mực máy in",
    "Computer": "Máy vi tính",
    "Computers": "Máy vi tính",
    "Monitor": "Màn hình hiển thị",
    "Monitors": "Màn hình hiển thị",
    "Network device": "Thiết bị mạng",
    "Network devices": "Thiết bị mạng",
    "Printer": "Máy in",
    "Printers": "Máy in",
    "Phone": "Điện thoại / Thiết bị viễn thông",
    "Phones": "Điện thoại / Thiết bị viễn thông",
    "Peripheral": "Thiết bị ngoại vi",
    "Peripherals": "Thiết bị ngoại vi",
    "Passive device": "Thiết bị thụ động",
    "Passive devices": "Thiết bị thụ động",
    "Rack": "Tủ rack thiết bị",
    "Racks": "Tủ rack thiết bị",
    "Enclosure": "Khung máy thiết bị",
    "Enclosures": "Khung máy thiết bị",
    "PDU": "Bộ phân phối nguồn PDU",
    "PDUs": "Bộ phân phối nguồn PDU",
    "Cable": "Cáp kết nối",
    "Cables": "Cáp kết nối",
    "Component": "Linh kiện tài sản",
    "Components": "Linh kiện tài sản",
    "Software": "Phần mềm",
    "Software license": "Bản quyền phần mềm",
    "Software licenses": "Bản quyền phần mềm",
    "Operating system": "Hệ điều hành",
    "Operating systems": "Hệ điều hành",
    
    # Nghiệp vụ tài chính & Kế toán tài sản công
    "Financial and administrative information": "Thông tin tài chính & Quản lý tài sản công",
    "Depreciation": "Hao mòn / Khấu hao",
    "Depreciations": "Hao mòn / Khấu hao",
    "Depreciation duration": "Thời gian tính hao mòn / Khấu hao",
    "Linear depreciation": "Khấu hao theo phương pháp đường thẳng",
    "Declining balance depreciation": "Khấu hao theo số dư giảm dần",
    "Purchase price": "Nguyên giá mua sắm",
    "Value": "Nguyên giá / Giá trị",
    "Residual value": "Giá trị còn lại của tài sản",
    "Net value": "Giá trị hiện tại còn lại",
    "Commissioning date": "Ngày đưa vào sử dụng",
    "Decommissioning date": "Ngày dừng sử dụng / Thanh lý",
    "Purchase date": "Ngày mua sắm / Tiếp nhận",
    "Warranty": "Bảo hành",
    "Warranty duration": "Thời hạn bảo hành",
    "Warranty expiration date": "Ngày hết hạn bảo hành",
    "Budget": "Dự toán ngân sách / Kinh phí",
    "Budgets": "Dự toán ngân sách / Kinh phí",
    "Contract": "Hợp đồng kinh tế / Mua sắm",
    "Contracts": "Hợp đồng kinh tế / Mua sắm",
    "Supplier": "Đơn vị cung cấp / Nhà thầu",
    "Suppliers": "Đơn vị cung cấp / Nhà thầu",
    "Contact": "Cán bộ phụ trách / Liên hệ",
    "Contacts": "Cán bộ phụ trách / Liên hệ",
    
    # Kiểm kê, Điều chuyển, Thanh lý
    "Inventory": "Kiểm kê tài sản",
    "Inventories": "Danh mục kiểm kê tài sản",
    "Inventory number": "Mã số kiểm kê / Mã tài sản",
    "Transfer": "Điều chuyển tài sản",
    "Transfers": "Điều chuyển tài sản",
    "Transfer an item": "Điều chuyển tài sản sang đơn vị khác",
    "Decommission": "Thanh lý tài sản",
    "Decommissioned": "Đã thanh lý",
    "Purge": "Xóa vĩnh viễn / Tiêu hủy",
    "Drop": "Thanh lý / Hủy bỏ",
    "Trash": "Chờ thanh lý / Thùng rác",
    "Put in trashbin": "Chuyển vào danh sách chờ thanh lý",
    "Restore": "Khôi phục tài sản",
    
    # Cơ cấu tổ chức & Vị trí
    "Entity": "Cơ quan / Đơn vị",
    "Entities": "Cơ quan / Đơn vị",
    "Location": "Vị trí / Địa điểm / Phòng ban",
    "Locations": "Vị trí / Địa điểm / Phòng ban",
    "State": "Hiện trạng sử dụng",
    "States": "Hiện trạng sử dụng",
    
    # Hỗ trợ kỹ thuật CNTT (ITSM / Helpdesk)
    "Helpdesk": "Hỗ trợ kỹ thuật CNTT",
    "Assistance": "Hỗ trợ kỹ thuật CNTT",
    "Ticket": "Phiếu yêu cầu / Hỗ trợ",
    "Tickets": "Phiếu yêu cầu / Hỗ trợ",
    "New ticket": "Tạo phiếu yêu cầu mới",
    "Incident": "Sự cố kỹ thuật",
    "Incidents": "Sự cố kỹ thuật",
    "Problem": "Vấn đề kỹ thuật",
    "Problems": "Vấn đề kỹ thuật",
    "Change": "Yêu cầu thay đổi",
    "Changes": "Yêu cầu thay đổi",
    "Project": "Dự án CNTT",
    "Projects": "Dự án CNTT",
    "Solution": "Giải pháp xử lý",
    "Solutions": "Giải pháp xử lý",
    "Knowledge base": "Cơ sở tri thức CNTT",
    "Knowledge Base": "Cơ sở tri thức CNTT",
    "FAQ": "Câu hỏi thường gặp (FAQ)",
    
    # Hệ thống & Tác tử
    "Administration": "Quản trị hệ thống",
    "Setup": "Cấu hình hệ thống",
    "Tools": "Công cụ quản lý",
    "Management": "Quản lý tài sản",
    "Agent": "Tác tử thu thập (GLPI Agent)",
    "Agents": "Tác tử thu thập (GLPI Agent)",
    "GLPI Agent": "Tác tử GLPI Agent",
    "Network discovery": "Dò quét phát hiện thiết bị mạng",
    "Network inventory": "Kiểm kê thiết bị mạng qua SNMP",
    "Rules": "Quy tắc tự động hóa",
    "Rule": "Quy tắc",
    "Profile": "Vai trò phân quyền",
    "Profiles": "Vai trò phân quyền",
    "User": "Người dùng",
    "Users": "Người dùng",
    "Group": "Nhóm quản lý",
    "Groups": "Nhóm quản lý"
}

# Các cụm từ cần thay thế chuẩn hoá sau dịch tự động
TERM_REPLACEMENTS = [
    (r'\bthực thể\b', 'đơn vị'),
    (r'\bThực thể\b', 'Đơn vị'),
    (r'\bcác thực thể\b', 'các đơn vị'),
    (r'\bCác thực thể\b', 'Các đơn vị'),
    (r'\bvé\b', 'phiếu yêu cầu'),
    (r'\bVé\b', 'Phiếu yêu cầu'),
    (r'\bmặt hàng\b', 'tài sản'),
    (r'\bMặt hàng\b', 'Tài sản'),
    (r'\bcác mặt hàng\b', 'các tài sản'),
    (r'\bCác mặt hàng\b', 'Các tài sản'),
    (r'\bthùng rác\b', 'danh sách chờ thanh lý'),
    (r'\bThùng rác\b', 'Danh sách chờ thanh lý'),
    (r'\bkhoản mục\b', 'tài sản'),
    (r'\bKhoản mục\b', 'Tài sản'),
    (r'\bđại lý\b', 'tác tử (Agent)'),
    (r'\bĐại lý\b', 'Tác tử (Agent)'),
]

def load_cache():
    if os.path.exists(CACHE_FILE):
        try:
            with open(CACHE_FILE, 'r', encoding='utf-8') as f:
                return json.load(f)
        except Exception:
            return {}
    return {}

def save_cache(cache):
    with open(CACHE_FILE, 'w', encoding='utf-8') as f:
        json.dump(cache, f, ensure_ascii=False, indent=2)

TOKEN_PATTERNS = [
    re.compile(r'%[0-9]+\$[a-zA-Z]'),           # %1$s, %2$d
    re.compile(r'%[-+ #0]*[0-9]*\.?[0-9]*[a-zA-Z%]'), # %s, %d, %02d, %%
    re.compile(r'\{[a-zA-Z0-9_\-]+\}'),         # {count}, {name}
    re.compile(r'<[^>]+>'),                      # HTML tags <b>, </div>
    re.compile(r'&[a-zA-Z0-9#]+;'),              # &laquo;, &nbsp;
]

def mask_text(text):
    tokens = []
    # Collect all matches with their start and end
    matches = []
    for pat in TOKEN_PATTERNS:
        for m in pat.finditer(text):
            matches.append((m.start(), m.end(), m.group(0)))
    
    # Sort matches by start position, resolve overlaps
    matches.sort(key=lambda x: x[0])
    non_overlapping = []
    last_end = 0
    for start, end, tok in matches:
        if start >= last_end:
            non_overlapping.append((start, end, tok))
            last_end = end
            
    # Build masked string
    parts = []
    last_end = 0
    for start, end, tok in non_overlapping:
        parts.append(text[last_end:start])
        idx = len(tokens)
        tokens.append(tok)
        parts.append(f'__T{idx}__')
        last_end = end
    parts.append(text[last_end:])
    
    return ''.join(parts), tokens

def unmask_text(text, tokens):
    for idx, tok in enumerate(tokens):
        # Match __T0__, __ T0 __, __t0__, etc.
        pattern = rf'__\s*[tT]\s*{idx}\s*__'
        text = re.sub(pattern, tok, text)
    return text

def apply_postprocess(text):
    for pat, repl in TERM_REPLACEMENTS:
        text = re.sub(pat, repl, text)
    return text

def translate_batch_gtx(batch_texts):
    """
    Translates a list of masked texts using Google GTX endpoint.
    """
    delimiter = '\n@@@\n'
    joined = delimiter.join(batch_texts)
    url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=' + urllib.parse.quote(joined)
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
    
    for attempt in range(5):
        try:
            with urllib.request.urlopen(req, timeout=25) as resp:
                data = json.loads(resp.read().decode('utf-8'))
                raw_full = ''.join([part[0] for part in data[0] if part[0]])
                results = [r.strip() for r in raw_full.split('@@@')]
                if len(results) == len(batch_texts):
                    return results
                else:
                    # In case delimiter split was off, fallback to one-by-one for this small batch
                    break
        except Exception as e:
            time.sleep(1 + attempt * 2)
    
    # Fallback: one-by-one translation
    fallback_results = []
    for item in batch_texts:
        if not item.strip():
            fallback_results.append('')
            continue
        u = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=vi&dt=t&q=' + urllib.parse.quote(item)
        r = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
        try:
            with urllib.request.urlopen(r, timeout=15) as resp:
                data = json.loads(resp.read().decode('utf-8'))
                fallback_results.append(''.join([p[0] for p in data[0] if p[0]]))
        except Exception:
            fallback_results.append(item)
        time.sleep(0.05)
    return fallback_results

def run_translation():
    print(f'Đang tải tập tin PO: {PO_FILE}...')
    po = polib.pofile(PO_FILE)
    total_entries = len(po)
    print(f'Tổng số mục trong vi_VN.po: {total_entries}')
    
    cache = load_cache()
    print(f'Đã tải {len(cache)} bản ghi từ cache ({CACHE_FILE}).')
    
    # Xác định các mục cần dịch
    to_translate = []
    for entry in po:
        # Nếu chưa dịch hoặc chuỗi msgstr rỗng
        is_empty = False
        if entry.msgid_plural:
            if not entry.msgstr_plural or not entry.msgstr_plural.get(0, '').strip():
                is_empty = True
        else:
            if not entry.msgstr or not entry.msgstr.strip():
                is_empty = True
        
        # Nếu đã có fuzzy flag, cũng cần cập nhật
        if 'fuzzy' in entry.flags:
            is_empty = True
            
        if is_empty:
            to_translate.append(entry)
            
    print(f'Số mục cần dịch hoàn thiện: {len(to_translate)}')
    
    # Lọc danh sách unique msgid cần gọi API
    needed_msgids = []
    for entry in to_translate:
        mid = entry.msgid.strip()
        if mid and mid not in cache and mid not in GLOSSARY:
            needed_msgids.append(mid)
        if entry.msgid_plural:
            mp = entry.msgid_plural.strip()
            if mp and mp not in cache and mp not in GLOSSARY:
                needed_msgids.append(mp)
                
    needed_unique = list(dict.fromkeys(needed_msgids))
    print(f'Số chuỗi duy nhất cần dịch từ API: {len(needed_unique)}')
    
    # Thực hiện dịch theo batch
    batch_size = 15
    for i in range(0, len(needed_unique), batch_size):
        chunk = needed_unique[i:i+batch_size]
        masked_batch = []
        token_batch = []
        for text in chunk:
            masked, tokens = mask_text(text)
            masked_batch.append(masked)
            token_batch.append(tokens)
            
        trans_results = translate_batch_gtx(masked_batch)
        for orig, res, tokens in zip(chunk, trans_results, token_batch):
            unmasked = unmask_text(res, tokens)
            processed = apply_postprocess(unmasked)
            cache[orig] = processed
            
        if (i // batch_size) % 10 == 0 or (i + batch_size >= len(needed_unique)):
            print(f'  -> Tiến độ dịch API: {min(i + batch_size, len(needed_unique))}/{len(needed_unique)} ({(min(i + batch_size, len(needed_unique))/len(needed_unique))*100:.1f}%)')
            save_cache(cache)
            
        time.sleep(0.08)
        
    save_cache(cache)
    print('Hoàn thành việc dịch toàn bộ chuỗi!')
    
    # Áp dụng bản dịch vào đối tượng PO
    print('Đang áp dụng bản dịch vào PO...')
    updated_count = 0
    for entry in po:
        # Nếu có trong Glossary ưu tiên cao nhất
        mid = entry.msgid.strip()
        
        # Xử lý mục đơn
        if not entry.msgid_plural:
            if not entry.msgstr or not entry.msgstr.strip() or 'fuzzy' in entry.flags:
                if mid in GLOSSARY:
                    entry.msgstr = GLOSSARY[mid]
                elif mid in cache:
                    entry.msgstr = cache[mid]
                else:
                    entry.msgstr = entry.msgid
                updated_count += 1
            else:
                # Kiểm tra và chuẩn hoá thuật ngữ cho các mục đã dịch từ trước
                entry.msgstr = apply_postprocess(entry.msgstr)
        else:
            # Xử lý số nhiều (tiếng Việt chỉ có 1 dạng số nhiều: plural index 0)
            if not entry.msgstr_plural or not entry.msgstr_plural.get(0, '').strip() or 'fuzzy' in entry.flags:
                if mid in GLOSSARY:
                    entry.msgstr_plural[0] = GLOSSARY[mid]
                elif mid in cache:
                    entry.msgstr_plural[0] = cache[mid]
                else:
                    entry.msgstr_plural[0] = entry.msgid
                updated_count += 1
            else:
                entry.msgstr_plural[0] = apply_postprocess(entry.msgstr_plural.get(0, ''))
                
        # Xoá cờ fuzzy nếu có
        if 'fuzzy' in entry.flags:
            entry.flags.remove('fuzzy')

    # Cập nhật metadata
    po.metadata['Language'] = 'vi_VN'
    po.metadata['Plural-Forms'] = 'nplurals=1; plural=0;'
    po.metadata['Content-Type'] = 'text/plain; charset=UTF-8'
    po.metadata['Last-Translator'] = 'Antigravity AI <vietnamese-public-asset-management@glpi.local>'

    # Lưu tập tin vi_VN.po
    print(f'Đang lưu tập tin PO: {PO_FILE}...')
    po.save(PO_FILE)

    # Biên dịch sang vi_VN.mo bằng polib
    print(f'Đang biên dịch tập tin MO: {MO_FILE}...')
    po.save_as_mofile(MO_FILE)

    # Thống kê kết quả
    trans_total = len(po.translated_entries())
    pct = po.percent_translated()
    print(f'=== KẾT QUẢ VIỆT HOÁ ===')
    print(f'Tổng số mục: {len(po)}')
    print(f'Đã dịch: {trans_total}/{len(po)} ({pct}%)')
    print(f'Số mục chưa dịch: {len(po.untranslated_entries())}')
    print(f'Số mục nghi ngờ (fuzzy): {len(po.fuzzy_entries())}')

if __name__ == '__main__':
    run_translation()
