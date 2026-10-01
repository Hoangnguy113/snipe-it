<?php

/*
 * Danh mục thiết bị ngang menu GLPI - spec 2026-09-30 mục 18.
 *
 * 'fields'  : kho trường tùy chỉnh. Khoá ngắn => định nghĩa. Tên trường phải duy nhất toàn hệ thống.
 * 'entries' : 16 mục. Mỗi mục tài sản sinh 1 danh mục + 1 bộ trường + 1 mô-đen mẫu.
 *
 * KHÔNG khai ở đây các trường Snipe-IT đã có sẵn: tên, số sê-ri, asset tag (số hàng tồn kho),
 * nhà sản xuất, mô-đen, trạng thái, vị trí, người dùng, ghi chú (bình luận), danh mục (kiểu).
 * "Toàn cục" của GLPI = Tìm kiếm chung sẵn có của Snipe-IT nên không có mục ở đây.
 */

$tech = ['tech_user', 'tech_group'];
$contact = ['contact', 'contact_num'];

return [
    'fields' => [
        'tech_user' => ['name' => 'Kỹ thuật viên phụ trách', 'element' => 'text'],
        'tech_group' => ['name' => 'Nhóm phụ trách', 'element' => 'text'],
        'contact' => ['name' => 'Tên người dùng thay thế', 'element' => 'text'],
        'contact_num' => ['name' => 'Số người dùng thay thế', 'element' => 'text'],
        'groups' => ['name' => 'Nhóm', 'element' => 'text'],
        'network' => ['name' => 'Mạng', 'element' => 'text'],
        'brand' => ['name' => 'Thương hiệu', 'element' => 'text'],
        'sysdescr' => ['name' => 'Mô tả hệ thống (sysdescr)', 'element' => 'textarea'],
        'rack_parent' => ['name' => 'Rack chứa thiết bị', 'element' => 'text'],
        'rack_unit' => ['name' => 'Vị trí U trong rack', 'element' => 'text', 'format' => 'NUMERIC'],

        'monitor_size' => ['name' => 'Kích thước màn hình (inch)', 'element' => 'text', 'format' => 'NUMERIC'],
        'monitor_ports' => [
            'name' => 'Cổng và tính năng màn hình',
            'element' => 'checkbox',
            'field_values' => "Micro\nLoa\nD-sub\nBNC\nDVI\nPivot\nHDMI\nDisplayPort",
        ],

        'ne_ram' => ['name' => 'RAM thiết bị mạng (MB)', 'element' => 'text', 'format' => 'NUMERIC'],
        'ne_cpu' => ['name' => 'CPU thiết bị mạng', 'element' => 'text'],
        'ne_firmware' => ['name' => 'Firmware thiết bị mạng', 'element' => 'text'],
        'ne_uptime' => ['name' => 'Thời gian hoạt động thiết bị mạng', 'element' => 'text'],

        'printer_ports' => [
            'name' => 'Cổng kết nối máy in',
            'element' => 'checkbox',
            'field_values' => "Serial\nParallel\nUSB\nWiFi\nEthernet",
        ],
        'printer_firmware' => ['name' => 'Firmware máy in', 'element' => 'text'],
        'printer_memory' => ['name' => 'Bộ nhớ máy in (MB)', 'element' => 'text', 'format' => 'NUMERIC'],
        'pages_init' => ['name' => 'Số trang in ban đầu', 'element' => 'text', 'format' => 'NUMERIC'],
        'pages_last' => ['name' => 'Số trang in gần nhất', 'element' => 'text', 'format' => 'NUMERIC'],

        'phone_line' => ['name' => 'Số đường dây', 'element' => 'text'],
        'phone_power' => ['name' => 'Nguồn cấp điện điện thoại', 'element' => 'text'],
        'phone_accessories' => [
            'name' => 'Phụ kiện điện thoại',
            'element' => 'checkbox',
            'field_values' => "Tai nghe\nLoa ngoài",
        ],

        'rack_width' => ['name' => 'Chiều rộng rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_height' => ['name' => 'Chiều cao rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_depth' => ['name' => 'Chiều sâu rack (mm)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_units' => ['name' => 'Số U của rack', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_room' => ['name' => 'Phòng máy', 'element' => 'text'],
        'rack_position' => ['name' => 'Vị trí trong phòng máy', 'element' => 'text'],
        'rack_orientation' => ['name' => 'Hướng đặt rack', 'element' => 'text'],
        'rack_color' => ['name' => 'Màu nền rack', 'element' => 'text'],
        'rack_max_power' => ['name' => 'Công suất tối đa rack (W)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_measured_power' => ['name' => 'Công suất đo được rack (W)', 'element' => 'text', 'format' => 'NUMERIC'],
        'rack_max_weight' => ['name' => 'Tải trọng tối đa rack (kg)', 'element' => 'text', 'format' => 'NUMERIC'],

        'enc_orientation' => ['name' => 'Hướng đặt khung máy', 'element' => 'text'],
        'enc_power' => ['name' => 'Số nguồn cấp khung máy', 'element' => 'text', 'format' => 'NUMERIC'],

        'pdu_type' => ['name' => 'Loại PDU', 'element' => 'text'],
        'pdu_ports' => ['name' => 'Số cổng PDU', 'element' => 'text', 'format' => 'NUMERIC'],

        'um_ip' => ['name' => 'Địa chỉ IP thiết bị', 'element' => 'text', 'format' => 'IP'],
        'um_hub' => ['name' => 'Là hub', 'element' => 'listbox', 'field_values' => "Có\nKhông"],

        'cable_type' => ['name' => 'Loại cáp', 'element' => 'text'],
        'cable_color' => ['name' => 'Màu cáp', 'element' => 'text'],
        'cable_end_a' => ['name' => 'Đầu cáp A (thiết bị và cổng)', 'element' => 'text'],
        'cable_end_b' => ['name' => 'Đầu cáp B (thiết bị và cổng)', 'element' => 'text'],
        'cable_strands' => ['name' => 'Số sợi cáp', 'element' => 'text', 'format' => 'NUMERIC'],

        'sim_pin' => ['name' => 'Mã PIN SIM', 'element' => 'text', 'encrypted' => true],
        'sim_pin2' => ['name' => 'Mã PIN 2 SIM', 'element' => 'text', 'encrypted' => true],
        'sim_puk' => ['name' => 'Mã PUK SIM', 'element' => 'text', 'encrypted' => true],
        'sim_puk2' => ['name' => 'Mã PUK 2 SIM', 'element' => 'text', 'encrypted' => true],
        'sim_msin' => ['name' => 'MSIN SIM', 'element' => 'text'],
        'sim_line' => ['name' => 'Số thuê bao', 'element' => 'text'],
        'sim_carrier' => ['name' => 'Nhà mạng SIM', 'element' => 'text'],
        'sim_type' => ['name' => 'Loại thẻ SIM', 'element' => 'text'],
        'sim_voltage' => ['name' => 'Điện áp SIM', 'element' => 'text'],
        'sim_voip' => ['name' => 'Cho phép VoIP', 'element' => 'listbox', 'field_values' => "Có\nKhông"],
    ],

    'entries' => [
        'computers' => [
            'category' => 'Máy tính', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'network'],
        ],
        'monitors' => [
            'category' => 'Màn hình', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'monitor_size', 'monitor_ports'],
        ],
        'software' => ['category' => 'Phần mềm', 'type' => 'license'],
        'network_equipment' => [
            'category' => 'Thiết bị mạng', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'network', 'ne_ram', 'ne_cpu', 'ne_firmware', 'ne_uptime', 'sysdescr'],
        ],
        'peripherals' => [
            'category' => 'Thiết bị ngoại vi', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'brand'],
        ],
        'printers' => [
            'category' => 'Máy in', 'type' => 'asset',
            'fields' => [
                ...$tech, ...$contact, 'groups', 'network',
                'printer_ports', 'printer_memory', 'printer_firmware', 'pages_init', 'pages_last', 'sysdescr',
            ],
        ],
        'cartridges' => ['category' => 'Hộp mực', 'type' => 'consumable'],
        'consumables' => ['category' => 'Hàng tiêu dùng', 'type' => 'consumable'],
        'phones' => [
            'category' => 'Điện thoại', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'groups', 'brand', 'phone_line', 'phone_power', 'phone_accessories'],
        ],
        'racks' => [
            'category' => 'Tủ rack', 'type' => 'asset',
            'fields' => [
                ...$tech, 'rack_width', 'rack_height', 'rack_depth', 'rack_units', 'rack_room',
                'rack_position', 'rack_orientation', 'rack_color', 'rack_max_power',
                'rack_measured_power', 'rack_max_weight',
            ],
        ],
        'enclosures' => [
            'category' => 'Khung máy', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit', 'enc_orientation', 'enc_power'],
        ],
        'pdus' => [
            'category' => 'Bộ phân phối nguồn (PDU)', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit', 'pdu_type', 'pdu_ports'],
        ],
        'passive_equipment' => [
            'category' => 'Thiết bị thụ động', 'type' => 'asset',
            'fields' => [...$tech, 'rack_parent', 'rack_unit'],
        ],
        'unmanaged' => [
            'category' => 'Nội dung không được quản lý', 'type' => 'asset',
            'fields' => [...$tech, ...$contact, 'network', 'um_ip', 'um_hub', 'sysdescr'],
        ],
        'cables' => [
            'category' => 'Cáp kết nối', 'type' => 'asset',
            'fields' => [...$tech, 'cable_type', 'cable_color', 'cable_end_a', 'cable_end_b', 'cable_strands'],
        ],
        'simcards' => [
            'category' => 'Thẻ SIM', 'type' => 'asset',
            'fields' => [
                ...$tech, 'sim_pin', 'sim_pin2', 'sim_puk', 'sim_puk2',
                'sim_msin', 'sim_line', 'sim_carrier', 'sim_type', 'sim_voltage', 'sim_voip',
            ],
        ],
    ],
];
