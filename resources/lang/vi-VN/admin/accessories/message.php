<?php

return [
    'does_not_exist' => 'Phụ kiện [:id] không tồn tại.',
    'not_found' => 'Không tìm thấy phụ kiện đó.',
    'assoc_users' => 'Phụ kiện này hiện có :count cái đã giao cho người dùng. Bạn hãy nhập lại vào trong phần phụ kiện và thử lại lần nữa. ',
    'create' => [
        'error' => 'Phụ kiện chưa tạo, hãy thử một lần nữa.',
        'success' => 'Phụ kiện đã được tạo thành công.',
    ],
    'update' => [
        'error' => 'Phụ kiện chưa được cập nhật, vui lòng thử lại',
        'success' => 'Phụ kiện đã được cập nhật thành công.',
    ],
    'delete' => [
        'confirm' => 'Bạn có chắc muốn xoá phụ kiện này không?',
        'error' => 'Có lỗi xảy ra khi xoá phụ kiện. Vui lòng thử lại.',
        'success' => 'Phụ kiện đã được xoá thành công.',
        'bulk_success' => 'Đã xóa phụ kiện thành công.|Đã xóa thành công :count phụ kiện.',
        'partial_success' => 'Đã xóa thành công :count phụ kiện, nhưng những cái khác không thể xóa. Xem chi tiết bên dưới.',
    ],
    'checkout' => [
        'error' => 'Phụ kiện chưa được xuất kho. Bạn hãy thử lại',
        'success' => 'Phụ kiện được xuất kho thành công.',
        'unavailable' => 'Không có sẵn phụ kiện để xuất. Hãy kiểm tra số lượng có sẵn',
        'user_does_not_exist' => 'Người dùng đó không hợp lệ. Vui lòng thử lại.',
        'checkout_qty' => [
            'lte' => 'Hiện chỉ có một phụ kiện trống loại này, và bạn đang cố gắng cấp phát :checkout_qty. Vui lòng điều chỉnh lại số lượng.|Hiện có :number_currently_remaining phụ kiện trống, và bạn đang cố gắng cấp phát :checkout_qty. Vui lòng điều chỉnh lại số lượng.',
        ],
    ],
    'checkin' => [
        'error' => 'Phụ kiện chưa được kho. Bạn hãy thử lại',
        'success' => 'Phuk kiện được nhập kho thành công.',
        'user_does_not_exist' => 'Người dùng này không tồn tại. Bạn hãy thử lại.',
    ],
];
