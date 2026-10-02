<?php

return [
    'does_not_exist' => 'Bản quyền không tồn tại hoặc bạn không có quyền xem.',
    'user_does_not_exist' => 'Người dùng không tồn tại hoặc bạn không có quyền xem họ.',
    'asset_does_not_exist' => 'Tài sản bản đang cố gắng liên kết với bản quyền này không tồn tại.',
    'owner_doesnt_match_asset' => 'Tài sản bạn đang cố gắng liên kết với bản quyền đã được sở hữu hởi một người nào đó khác với người đang được lựa chọn để gán trong danh sách xổ xuống.',
    'assoc_users' => 'Bản quyền này hiện đã được cấp phát cho một người dùng và không thể xóa. Vui lòng thu hồi bản quyền trước rồi thử lại.',
    'select_asset_or_person' => 'Bạn phải chọn một nội dung hoặc người dùng, nhưng không phải cả hai.',
    'not_found' => 'Không tìm thấy bản quyền',
    'seats_available' => 'Còn :seat_count slot trống',
    'create' => [
        'error' => 'Bản quyền chưa được tạo, vui lòng thử lại.',
        'success' => 'Bản quyền đã được tạo thành công.',
    ],
    'deletefile' => [
        'error' => 'Tập tin không xóa được. Xin vui lòng thử lại.',
        'success' => 'Tập tin đã xóa thành công.',
    ],
    'upload' => [
        'error' => 'Tập tin không tải lên được. Xin vui lòng thử lại.',
        'success' => 'Tập tin đã tải lên thành công.',
        'nofiles' => 'Bạn chưa chọn bất kỳ tập tin nào để tải lên, hoặc tập tin bạn đang cố gắng tải lên có dung lượng quá lớn',
        'invalidfiles' => 'Một hoặc nhiều tệp của bạn quá lớn hoặc là loại tập tin không được phép. Các loại tệp được cho phép là png, gif, jpg, jpeg, doc, docx, pdf, txt, zip, rar, rtf, xml và lic.',
    ],
    'update' => [
        'error' => 'Bản quyền chưa được cập nhật, vui lòng thử lại.',
        'success' => 'Bản quyền đã được cập nhật thành công.',
    ],
    'delete' => [
        'confirm' => 'Bạn có chắc muốn xóa bản quyền này?',
        'error' => 'Có sự cố khi xóa bản quyền. Vui lòng thử lại.',
        'success' => 'Bản quyền đã được xóa thành công.',
        'bulk_success' => 'Đã xóa bản quyền thành công.|Đã xóa thành công :count bản quyền.',
        'partial_success' => 'Đã xóa bản quyền thành công. Xem chi tiết bên dưới.|Đã xóa thành công :count bản quyền.',
        'bulk_checkout_warning' => ':license_name có các slot đang được cấp phát và không thể xóa. Vui lòng thu hồi tất cả các slot trước khi xóa.',
    ],
    'delete_with_checkin' => [
        'bulk_success' => ':count bản quyền đã được xóa thành công sau khi thu hồi :seats slot.',
        'partial_success' => ':count bản quyền đã được xóa thành công sau khi thu hồi :seats slot. Xem thêm thông tin bên dưới.',
    ],
    'checkout' => [
        'error' => 'Có sự cố khi cấp phát bản quyền. Vui lòng thử lại.',
        'success' => 'Bản quyền đã được cấp phát thành công.',
        'not_enough_seats' => 'Không đủ slot bản quyền khả dụng để cấp phát',
        'mismatch' => 'Slot bản quyền được cung cấp không khớp với bản quyền này',
        'unavailable' => 'Slot này không khả dụng để cấp phát.',
        'license_is_inactive' => 'Bản quyền này đã hết hạn hoặc bị chấm dứt.',
    ],
    'checkin' => [
        'error' => 'Có sự cố khi thu hồi bản quyền. Vui lòng thử lại.',
        'not_reassignable' => 'Slot đã được sử dụng và không thể tái cấp phát',
        'success' => 'Bản quyền đã được thu hồi thành công.',
    ],
    'assoc_assets' => 'Bản quyền này hiện đã được cấp phát cho một tài sản và không thể xóa. Vui lòng thu hồi bản quyền trước rồi thử lại.',
    'import' => [
        'no_free_seats' => 'Bản quyền ":license" không còn chỗ trống. ":target" chưa được gán bản quyền.',
    ],
];
