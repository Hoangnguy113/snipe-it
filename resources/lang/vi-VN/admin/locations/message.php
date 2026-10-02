<?php

return [
    'does_not_exist' => 'Vị trí không tồn tại.',
    'assoc_users' => 'Vị trí này hiện đang liên kết với ít nhất một người dùng và không thể xóa. Vui lòng cập nhật người dùng để không còn tham chiếu đến vị trí này và thử lại.',
    'assoc_assets' => 'Vị trí này hiện đang liên kết với ít nhất một tài sản và không thể xóa. Vui lòng cập nhật tài sản để không còn tham chiếu đến vị trí này và thử lại.',
    'assoc_child_loc' => 'Vị trí này hiện là vị trí cha của ít nhất một vị trí con và không thể xóa. Vui lòng cập nhật các vị trí của bạn để không còn tham chiếu đến vị trí này và thử lại.',
    'assigned_assets' => 'Tài sản được giao',
    'current_location' => 'Vị trí hiện tại',
    'deleted_warning' => 'Vị trí này đã bị xóa. Vui lòng khôi phục trước khi thực hiện thao tác.',
    'create' => [
        'error' => 'Vị trí chưa được tạo, xin vui lòng thử lại.',
        'success' => 'Vị trí đã được tạo thành công.',
    ],
    'update' => [
        'error' => 'Vị trí chưa được cập nhật, xin vui lòng thử lại.',
        'success' => 'Vị trí đã được cập nhật thành công.',
    ],
    'restore' => [
        'error' => 'Vị trí chưa được khôi phục, vui lòng thử lại.',
        'success' => 'Vị trí đã được khôi phục thành công.',
    ],
    'delete' => [
        'confirm' => 'Bạn có chắc muốn xóa vị trí này?',
        'error' => 'Có vấn đề xảy ra khi xóa vị trí. Xin vui lòng thử lại.',
        'success' => 'Vị trí đã được xóa thành công.',
    ],
    'bulkedit' => [
        'error' => 'Không có trường nào thay đổi, nên không có gì được cập nhật.',
        'success' => 'Cập nhật vị trí thành công.|Cập nhật thành công :count vị trí.',
        'warn' => 'Chỉnh sửa các trường bên dưới để cập nhật vị trí này. Các trường bạn để trống sẽ không thay đổi trên vị trí.|Chỉnh sửa các trường bên dưới để cập nhật tất cả :count vị trí đã chọn. Các trường bạn để trống sẽ không thay đổi trên bất kỳ vị trí nào.',
        'show_selected' => '1 vị trí đã chọn|:count vị trí đã chọn',
        'company_scope_mismatch_partial' => 'Đơn vị/Cơ quan không được thay đổi trên 1 vị trí vì các tài sản hoặc người dùng tại vị trí đó thuộc các đơn vị/cơ quan khác nhau. Hãy cập nhật hoặc chuyển các đối tượng đó trước.|Đơn vị/Cơ quan không được thay đổi trên :count vị trí vì các tài sản hoặc người dùng tại các vị trí đó thuộc các đơn vị/cơ quan khác nhau. Hãy cập nhật hoặc chuyển các đối tượng đó trước.',
        'company_scope_mismatch_all' => 'Không có vị trí nào được gán lại. Đơn vị/Cơ quan yêu cầu không khớp với các tài sản hoặc người dùng tại vị trí đã chọn.|Không có vị trí nào được gán lại. Đơn vị/Cơ quan yêu cầu không khớp với các tài sản hoặc người dùng tại bất kỳ vị trí nào trong :count vị trí đã chọn.',
        'parent_company_mismatch_partial' => 'Vị trí cha hoặc đơn vị/cơ quan không được thay đổi trên 1 vị trí vì điều đó sẽ khiến vị trí này khác đơn vị/cơ quan với vị trí cha của nó.|Vị trí cha hoặc đơn vị/cơ quan không được thay đổi trên :count vị trí vì điều đó sẽ khiến các vị trí này khác đơn vị/cơ quan với vị trí cha của chúng.',
        'parent_company_mismatch_all' => 'Không có thay đổi nào được lưu. Vị trí cha hoặc đơn vị/cơ quan được yêu cầu sẽ khiến vị trí khác đơn vị/cơ quan với vị trí cha.|Không có thay đổi nào được lưu. Vị trí cha hoặc đơn vị/cơ quan được yêu cầu sẽ khiến từng vị trí trong :count vị trí đã chọn khác đơn vị/cơ quan với vị trí cha.',
    ],
];
