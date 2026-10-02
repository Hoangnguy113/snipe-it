<?php

return [
    'deleted' => 'Model tài sản đã xóa',
    'does_not_exist' => 'Kiểu tài sản không tồn tại.',
    'no_association' => 'CẢNH BÁO! Model tài sản cho cho thiết bị này không hợp lệ hoặc bị thiếu!',
    'no_association_fix' => 'Điều này sẽ phá vỡ mọi thứ theo những cách kỳ lạ và khủng khiếp. Hãy chỉnh sửa tài sản này ngay bây giờ để gán cho nó một model.',
    'assoc_users' => 'Kiểu tài sản này hiện đang được liên kết với một hoặc nhiều tài sản và không thể xóa. Vui lòng xóa các tài sản đó, rồi thử lại.',
    'invalid_category_type' => 'Danh mục này phải là danh mục tài sản.',
    'create' => [
        'error' => 'Kiểu tài sản chưa được tạo, xin thử lại.',
        'success' => 'Kiểu tài sản đã tạo thành công.',
        'duplicate_set' => 'Kiểu tài sản này với tên, nhà sản xuất và mã tài sản thật sự đã tồn tại.',
    ],
    'update' => [
        'error' => 'Kiểu tài sản chưa cập nhật, xin thử lại',
        'success' => 'Kiểu tài sản đã cập nhật thành công.',
    ],
    'delete' => [
        'confirm' => 'Bạn có chắc muốn xóa kiểu tài sản này?',
        'error' => 'Có vấn đề xảy ra khi xóa kiểu tài sản. Xin thử lại.',
        'success' => 'Kiểu tài sản đã xóa thành công.',
    ],
    'restore' => [
        'error' => 'Kiểu tài sản chưa được phục hồi, vui lòng thử lại.',
        'success' => 'Kiểu tài sản đã được phục hồi thành công.',
    ],
    'bulkedit' => [
        'error' => 'Không có trường nào được thay đổi, vì vậy không có gì được cập nhật.',
        'success' => 'Model đã được cập nhật thành công. |:model_count models đã được cập nhật thành công.',
        'warn' => 'Bạn sắp cập nhật thuộc tính của kiểu tài sản:|Bạn sắp cập nhật thuộc tính của :count kiểu tài sản:',
    ],
    'bulkdelete' => [
        'error' => 'Không có mục nào được chọn, nên không có gì bị xóa cả.',
        'nothing_deletable' => 'Không có kiểu tài sản nào đã chọn có thể xóa được vì chúng vẫn còn tài sản liên kết.',
        'success' => 'Model đã xóa!|:success_count model đã xóa!',
        'success_partial' => ':success_count model(s) kiểu tài sản đã được xóa, tuy nhiên có :fail_count loại không cho phép xóa vì chúng vẫn còn gắn liên kết đết tài sản.',
    ],
];
