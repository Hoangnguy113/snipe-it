<?php

return [
    'does_not_exist' => 'Danh mục không tồn tại.',
    'assoc_models' => 'Danh mục này hiện đang liên kết với một kiểu nào đó nên không thể xoá. Vui lòng cập nhật kiểu có liên quan để ngừng liên kết với danh mục này và thử xoá lại.',
    'assoc_items' => 'Danh mục này hiện đang liên kết với :asset_type nào đó nên không thể xoá. Vui lòng cập nhật  :asset_type có liên quan để ngừng liên kết với danh mục này và thử xoá lại.',
    'create' => [
        'error' => 'Hạng mục chưa được tạo. Bạn hãy thử lại.',
        'success' => 'Hạng mục đã được khởi tạo thành công.',
    ],
    'update' => [
        'error' => 'Hạng mục chưa được cập nhật. Bạn hãy thử lại',
        'success' => 'Hạng mục được cập nhật thành công.',
        'cannot_change_category_type' => 'Bạn không thể thay đổi loại danh mục một khi nó đã được tạo',
    ],
    'delete' => [
        'confirm' => 'Bạn có chắc chắn muốn xoá hạng mục này?',
        'error' => 'Có vấn đề xảy ra khi xoá hạng mục này. Bạn hãy thử lại.',
        'success' => 'Danh mục đã được xóa thành công.',
        'bulk_success' => 'Đã xóa danh mục thành công.|Đã xóa thành công :count danh mục.',
        'partial_success' => 'Đã xóa danh mục thành công. Xem chi tiết bên dưới.|Đã xóa thành công :count danh mục.',
    ],
    'import_require_acceptance_invalid' => 'Giá trị Yêu cầu chấp nhận không hợp lệ. Phải là true, false, 0 hoặc 1.',
    'import_checkin_email_invalid' => 'Giá trị Gửi email thu hồi không hợp lệ. Phải là true, false, 0 hoặc 1.',
];
