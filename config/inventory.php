<?php

return [
    // HTTP Basic auth with the agent. The agent cannot send arbitrary headers -
    // Basic is the only scheme it supports (HTTP/Client.pm:271-300).
    'agent_user' => env('INVENTORY_AGENT_USER', 'qlts-agent'),
    'agent_secret' => env('INVENTORY_AGENT_SECRET'),

    // QĐ-14: Kho là 1 bản ghi locations do quản trị tự tạo; máy chưa có vị trí được điền vào đây.
    'stock_location_id' => env('INVENTORY_STOCK_LOCATION_ID'),

    // Thay đổi critical (đổi serial máy / mainboard) luôn gửi email ngay tới địa chỉ này (bỏ trống = không gửi).
    'alert_email' => env('INVENTORY_ALERT_EMAIL'),

    // QĐ-8: điều khiển từ xa yêu cầu tài khoản đã bật 2FA (mặc định bật; quản trị tắt được).
    'remote_require_2fa' => (bool) env('INVENTORY_REMOTE_REQUIRE_2FA', true),

    // RB-7 / spec 12.2: cài app từ xa TẮT cho tới khi HTTPS hoạt động. Bật bằng INVENTORY_DEPLOY_ENABLED=true.
    // deploy_allow_http chỉ dùng cho test, KHÔNG bật ở môi trường thật.
    'deploy_enabled' => (bool) env('INVENTORY_DEPLOY_ENABLED', false),
    'deploy_allow_http' => (bool) env('INVENTORY_DEPLOY_ALLOW_HTTP', false),

    // Default thresholds - spec section 6.2
    'inventory_interval_hours' => (int) env('INVENTORY_INTERVAL_HOURS', 24),
    'deploy_poll_hours' => (int) env('INVENTORY_DEPLOY_POLL_HOURS', 4),
    'stale_inventory_days' => (int) env('INVENTORY_STALE_DAYS', 7),
    'stale_heartbeat_minutes' => (int) env('INVENTORY_STALE_HEARTBEAT_MINUTES', 15),
    'heartbeat_interval_minutes' => (int) env('INVENTORY_HEARTBEAT_INTERVAL_MINUTES', 5),
    'low_disk_percent' => (int) env('INVENTORY_LOW_DISK_PERCENT', 10),
    'battery_worn_percent' => (int) env('INVENTORY_BATTERY_WORN_PERCENT', 60),
    'heartbeat_retention_days' => (int) env('INVENTORY_HEARTBEAT_RETENTION_DAYS', 30),
    'snapshot_retention_count' => (int) env('INVENTORY_SNAPSHOT_RETENTION_COUNT', 10),
];
