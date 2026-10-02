<?php

return [
    'superuser' => [
        'name' => 'Quản trị tối cao (Super User)',
        'note' => 'Xác định người dùng có toàn quyền truy cập vào tất cả các phần của hệ thống. Cài đặt này ghi đè TẤT CẢ các quyền hạn chế khác trong toàn hệ thống.',
    ],
    'admin' => [
        'name' => 'Quyền Quản trị viên',
        'note' => 'Xác định người dùng có quyền truy cập vào hầu hết các khía cạnh của hệ thống NGOẠI TRỪ Cài đặt Quản trị Hệ thống. Những người dùng này sẽ có thể quản lý người dùng, vị trí, danh mục, v.v., nhưng bị giới hạn bởi Hỗ trợ đa đơn vị/cơ quan (FMCS) nếu được bật.',
    ],
    'import' => [
        'name' => 'Nhập dữ liệu CSV',
        'note' => 'Cho phép người dùng thực hiện nhập dữ liệu ngay cả khi quyền truy cập người dùng, tài sản, v.v. bị từ chối ở nơi khác.',
    ],

    'inventory' => [
        'name' => 'Kiểm kê tự động',
        'note' => 'Nhóm quyền cho kiểm kê tự động và điều khiển từ xa.',
    ],

    'inventoryview' => [
        'name' => 'Xem kiểm kê',
        'note' => 'Xem tab Kiểm kê và bảng trạng thái máy trạm.',
    ],

    'inventoryapprove' => [
        'name' => 'Duyệt thay đổi kiểm kê',
        'note' => 'Duyệt hoặc từ chối thay đổi linh kiện và nhãn khoa phòng.',
    ],

    'remotecontrol' => [
        'name' => 'Điều khiển từ xa',
        'note' => 'Mở phiên điều khiển từ xa máy trạm.',
    ],

    'remotedeploy' => [
        'name' => 'Cài ứng dụng từ xa',
        'note' => 'Gửi lệnh cài ứng dụng tới máy trạm.',
    ],

    'reports' => [
        'name' => 'Xem báo cáo',
        'note' => 'Xác định người dùng có quyền truy cập vào phần Báo cáo của ứng dụng.',
    ],
    'assets' => [
        'name' => 'Quản lý Tài sản',
        'note' => 'Cấp quyền truy cập vào phần Quản lý Tài sản của ứng dụng.',
    ],
    'assetsview' => [
        'name' => 'Xem tài sản',
        'note' => 'Người dùng có quyền này cũng có thể xem (không sửa hoặc xóa) các tệp đính kèm được tải lên kiểu tài sản để chia sẻ tài liệu chung như hướng dẫn sử dụng.',
    ],
    'assetscreate' => [
        'name' => 'Tạo mới tài sản',
    ],
    'assetsedit' => [
        'name' => 'Chỉnh sửa tài sản',
    ],
    'assetsdelete' => [
        'name' => 'Xóa tài sản',
    ],
    'assetscheckin' => [
        'name' => 'Thu hồi tài sản',
        'note' => 'Thu hồi tài sản đang được cấp phát trở lại kho.',
    ],
    'assetscheckout' => [
        'name' => 'Cấp phát tài sản',
        'note' => 'Cấp phát tài sản trong kho cho người dùng, vị trí hoặc tài sản khác.',
    ],
    'assetsaudit' => [
        'name' => 'Kiểm kê tài sản',
        'note' => 'Cho phép người dùng đánh dấu tài sản đã được kiểm kê thực tế.',
    ],
    'assetsviewrequestable' => [
        'name' => 'View Requestable Assets',
        'note' => 'Cho phép người dùng xem các tài sản được đánh dấu là có thể yêu cầu.',
    ],
    'assetsviewencrypted-custom-fields' => [
        'name' => 'Xem trường tùy chỉnh đã mã hóa',
        'note' => 'Cho phép người dùng xem và chỉnh sửa các trường tùy chỉnh được mã hóa trên tài sản.',
    ],
    'accessories' => [
        'name' => 'Phụ kiện',
        'note' => 'Cấp quyền truy cập vào phần Quản lý Phụ kiện của ứng dụng.',
    ],
    'accessoriesview' => [
        'name' => 'Xem phụ kiện',
    ],
    'accessoriescreate' => [
        'name' => 'Tạo phụ kiện',
    ],
    'accessoriesedit' => [
        'name' => 'Sửa phụ kiện',
    ],
    'accessoriesdelete' => [
        'name' => 'Xóa phụ kiện',
    ],
    'accessoriescheckout' => [
        'name' => 'Cấp phát phụ kiện',
        'note' => 'Cấp phát phụ kiện trong kho.',
    ],
    'accessoriescheckin' => [
        'name' => 'Thu hồi phụ kiện',
        'note' => 'Thu hồi phụ kiện đang được cấp phát trở lại kho.',
    ],
    'accessoriesfiles' => [
        'name' => 'Quản lý tệp phụ kiện',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp đính kèm với phụ kiện.',
    ],
    'assetsfiles' => [
        'name' => 'Quản lý tệp tài sản',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp đính kèm liên quan đến tài sản. (Chỉ có tác dụng khi có quyền xem trở lên.)',
    ],
    'usersfiles' => [
        'name' => 'Quản lý tệp người dùng',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của người dùng.',
    ],
    'modelsfiles' => [
        'name' => 'Quản lý tệp kiểu máy (model)',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp gắn với kiểu máy tài sản trên cả màn hình xem kiểu máy và xem tài sản. (Chỉ có tác dụng khi có quyền xem trở lên.)',
    ],
    'departmentsfiles' => [
        'name' => 'Quản lý tệp phòng ban',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của phòng ban.',
    ],
    'suppliersfiles' => [
        'name' => 'Quản lý tệp nhà cung cấp',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của nhà cung cấp.',
    ],
    'locationsfiles' => [
        'name' => 'Quản lý tệp vị trí',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của vị trí.',
    ],
    'companiesfiles' => [
        'name' => 'Quản lý tệp đơn vị/cơ quan',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của đơn vị/cơ quan.',
    ],
    'consumablesfiles' => [
        'name' => 'Quản lý tệp vật tư tiêu hao',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp đính kèm vật tư tiêu hao.',
    ],
    'consumables' => [
        'name' => 'Vật tư tiêu hao',
        'note' => 'Cấp quyền truy cập vào phần Vật tư tiêu hao của ứng dụng.',
    ],
    'consumablesview' => [
        'name' => 'Xem vật tư tiêu hao',
    ],
    'consumablescreate' => [
        'name' => 'Tạo vật tư tiêu hao',
    ],
    'consumablesedit' => [
        'name' => 'Sửa vật tư tiêu hao',
    ],
    'consumablesdelete' => [
        'name' => 'Xóa vật tư tiêu hao',
    ],
    'consumablescheckout' => [
        'name' => 'Cấp phát vật tư tiêu hao',
        'note' => 'Cấp phát vật tư tiêu hao cho người dùng.',
    ],
    'licenses' => [
        'name' => 'Bản quyền phần mềm',
        'note' => 'Cấp quyền truy cập vào phần Bản quyền của ứng dụng.',
    ],
    'licensesview' => [
        'name' => 'Xem bản quyền',
    ],
    'licensescreate' => [
        'name' => 'Tạo mới bản quyền',
    ],
    'licensesedit' => [
        'name' => 'Sửa bản quyền',
    ],
    'licensesdelete' => [
        'name' => 'Xóa bản quyền',
    ],
    'licensescheckout' => [
        'name' => 'Cấp phát bản quyền',
        'note' => 'Cấp phát chỗ bản quyền cho người dùng hoặc tài sản.',
    ],
    'licensescheckin' => [
        'name' => 'Thu hồi bản quyền',
        'note' => 'Thu hồi chỗ bản quyền phần mềm.',
    ],
    'licensesfiles' => [
        'name' => 'Quản lý tệp bản quyền',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp đính kèm bản quyền.',
    ],
    'componentsfiles' => [
        'name' => 'Quản lý tệp linh kiện',
        'note' => 'Cho phép người dùng tải lên, tải xuống và xóa các tệp đính kèm linh kiện.',
    ],
    'licenseskeys' => [
        'name' => 'Xem mã bản quyền (License Keys)',
        'note' => 'Cho phép người dùng xem các mã bản quyền phần mềm thực tế.',
    ],
    'components' => [
        'name' => 'Linh kiện',
        'note' => 'Cấp quyền truy cập vào phần Linh kiện của ứng dụng.',
    ],
    'componentsview' => [
        'name' => 'Xem linh kiện',
    ],
    'componentscreate' => [
        'name' => 'Tạo mới linh kiện',
    ],
    'componentsedit' => [
        'name' => 'Sửa linh kiện',
    ],
    'componentsdelete' => [
        'name' => 'Xóa linh kiện',
    ],
    'componentscheckout' => [
        'name' => 'Cấp phát linh kiện',
        'note' => 'Cấp phát linh kiện cho tài sản.',
    ],
    'componentscheckin' => [
        'name' => 'Thu hồi linh kiện',
        'note' => 'Thu hồi linh kiện từ tài sản về kho.',
    ],
    'kits' => [
        'name' => 'Gói cấu hình sẵn',
        'note' => 'Cấp quyền truy cập vào phần Gói cấu hình sẵn.',
    ],
    'kitsview' => [
        'name' => 'Xem gói cấu hình sẵn',
    ],
    'kitscreate' => [
        'name' => 'Tạo mới gói cấu hình sẵn',
    ],
    'kitsedit' => [
        'name' => 'Sửa gói cấu hình sẵn',
    ],
    'kitsdelete' => [
        'name' => 'Xóa gói cấu hình sẵn',
    ],
    'users' => [
        'name' => 'Người dùng',
        'note' => 'Cấp quyền truy cập vào phần Người dùng của ứng dụng.',
    ],
    'usersview' => [
        'name' => 'Xem người dùng',
    ],
    'userscreate' => [
        'name' => 'Tạo mới người dùng',
    ],
    'usersedit' => [
        'name' => 'Sửa người dùng',
    ],
    'usersdelete' => [
        'name' => 'Xóa người dùng',
    ],
    'models' => [
        'name' => 'Kiểu tài sản (Model)',
        'note' => 'Cấp quyền truy cập vào phần Kiểu tài sản.',
    ],
    'modelsview' => [
        'name' => 'Xem kiểu tài sản',
    ],
    'modelscreate' => [
        'name' => 'Tạo mới kiểu tài sản',
    ],
    'modelsedit' => [
        'name' => 'Sửa kiểu tài sản',
    ],
    'modelsdelete' => [
        'name' => 'Xóa kiểu tài sản',
    ],
    'categories' => [
        'name' => 'Danh mục',
        'note' => 'Cấp quyền truy cập vào phần Danh mục.',
    ],
    'categoriesview' => [
        'name' => 'Xem danh mục',
    ],
    'categoriescreate' => [
        'name' => 'Tạo mới danh mục',
    ],
    'categoriesedit' => [
        'name' => 'Sửa danh mục',
    ],
    'categoriesdelete' => [
        'name' => 'Xóa danh mục',
    ],
    'departments' => [
        'name' => 'Phòng ban',
        'note' => 'Cấp quyền truy cập vào phần Phòng ban.',
    ],
    'departmentsview' => [
        'name' => 'Xem phòng ban',
    ],
    'departmentscreate' => [
        'name' => 'Tạo mới phòng ban',
    ],
    'departmentsedit' => [
        'name' => 'Sửa phòng ban',
    ],
    'departmentsdelete' => [
        'name' => 'Xóa phòng ban',
    ],
    'locations' => [
        'name' => 'Vị trí',
        'note' => 'Cấp quyền truy cập vào phần Vị trí.',
    ],
    'locationsview' => [
        'name' => 'Xem vị trí',
    ],
    'locationscreate' => [
        'name' => 'Tạo mới vị trí',
    ],
    'locationsedit' => [
        'name' => 'Sửa vị trí',
    ],
    'locationsdelete' => [
        'name' => 'Xóa vị trí',
    ],
    'status-labels' => [
        'name' => 'Nhãn tình trạng',
        'note' => 'Cấp quyền truy cập vào phần Nhãn trạng thái của ứng dụng được sử dụng bởi Tài sản.',
    ],
    'statuslabelsview' => [
        'name' => 'Xem nhãn trạng thái',
    ],
    'statuslabelscreate' => [
        'name' => 'Tạo mới nhãn trạng thái',
    ],
    'statuslabelsedit' => [
        'name' => 'Sửa nhãn trạng thái',
    ],
    'statuslabelsdelete' => [
        'name' => 'Xóa nhãn trạng thái',
    ],
    'custom-fields' => [
        'name' => 'Trường tùy chỉnh',
        'note' => 'Cấp quyền truy cập vào phần Trường tùy chỉnh của ứng dụng được sử dụng bởi Tài sản.',
    ],
    'customfieldsview' => [
        'name' => 'Xem trường tùy chỉnh',
    ],
    'customfieldscreate' => [
        'name' => 'Tạo trường tùy chỉnh mới',
    ],
    'customfieldsedit' => [
        'name' => 'Chỉnh sửa trường tùy chỉnh',
    ],
    'customfieldsdelete' => [
        'name' => 'Xóa trường tùy chỉnh',
    ],
    'suppliers' => [
        'name' => 'Nhà cung cấp',
        'note' => 'Cấp quyền truy cập vào phần Nhà cung cấp.',
    ],
    'suppliersview' => [
        'name' => 'Xem nhà cung cấp',
    ],
    'supplierscreate' => [
        'name' => 'Tạo mới nhà cung cấp',
    ],
    'suppliersedit' => [
        'name' => 'Sửa nhà cung cấp',
    ],
    'suppliersdelete' => [
        'name' => 'Xóa nhà cung cấp',
    ],
    'manufacturers' => [
        'name' => 'Nhà sản xuất',
        'note' => 'Cấp quyền truy cập vào phần Nhà sản xuất.',
    ],
    'manufacturersview' => [
        'name' => 'Xem nhà sản xuất',
    ],
    'manufacturerscreate' => [
        'name' => 'Tạo mới nhà sản xuất',
    ],
    'manufacturersedit' => [
        'name' => 'Sửa nhà sản xuất',
    ],
    'manufacturersdelete' => [
        'name' => 'Xóa nhà sản xuất',
    ],
    'companies' => [
        'name' => 'Đơn vị/Cơ quan',
        'note' => 'Cấp quyền truy cập vào phần Đơn vị/Cơ quan.',
    ],
    'companiesview' => [
        'name' => 'Xem đơn vị/cơ quan',
    ],
    'companiescreate' => [
        'name' => 'Tạo mới đơn vị/cơ quan',
    ],
    'companiesedit' => [
        'name' => 'Sửa đơn vị/cơ quan',
    ],
    'companiesdelete' => [
        'name' => 'Xóa đơn vị/cơ quan',
    ],
    'user-self-accounts' => [
        'name' => 'Tự quản lý tài khoản cá nhân',
        'note' => 'Cấp cho người dùng thông thường (không phải admin) khả năng tự quản lý một số khía cạnh của tài khoản của chính họ.',
    ],
    'selftwo-factor' => [
        'name' => 'Quản lý xác thực hai yếu tố (2FA)',
        'note' => 'Cho phép người dùng tự bật, tắt và quản lý xác thực hai yếu tố cho tài khoản của họ.',
    ],
    'selfapi' => [
        'name' => 'Quản lý mã token API',
        'note' => 'Cho phép người dùng tự tạo, xem và thu hồi mã token API của chính mình. Token của người dùng sẽ có cùng quyền với người dùng đã tạo ra chúng.',
    ],
    'selfedit-location' => [
        'name' => 'Sửa vị trí cá nhân',
        'note' => 'Cho phép người dùng tự chỉnh sửa vị trí gắn liền với tài khoản của họ.',
    ],
    'selfcheckout-assets' => [
        'name' => 'Tự mượn / cấp phát tài sản',
        'note' => 'Cho phép người dùng tự mượn tài sản cho chính mình mà không cần admin can thiệp.',
    ],
    'selfview-purchase-cost' => [
        'name' => 'Xem giá mua tài sản',
        'note' => 'Cho phép người dùng xem giá mua của các mục trong màn hình xem tài khoản của họ.',
    ],
    'depreciations' => [
        'name' => 'Khấu hao',
        'note' => 'Cấp quyền truy cập vào phần Khấu hao.',
    ],
    'depreciationsview' => [
        'name' => 'Xem khấu hao',
    ],
    'depreciationsedit' => [
        'name' => 'Sửa khấu hao',
    ],
    'depreciationsdelete' => [
        'name' => 'Xóa khấu hao',
    ],
    'depreciationscreate' => [
        'name' => 'Tạo mới khấu hao',
    ],
    'grant_all' => 'Cấp tất cả các quyền cho :area',
    'deny_all' => 'Từ chối tất cả các quyền cho :area',
    'inherit_all' => 'Kế thừa tất cả các quyền cho :area từ nhóm quyền',
    'grant' => 'Cấp quyền cho :area',
    'deny' => 'Từ chối quyền cho :area',
    'inherit' => 'Kế thừa quyền cho :area từ nhóm quyền',
    'use_groups' => 'Chúng tôi khuyến nghị nên sử dụng Nhóm quyền thay vì gán quyền cá nhân để quản lý dễ dàng hơn.',
    'consumablescheckin' => [
        'name' => 'Thu hồi vật tư tiêu hao',
        'note' => 'Thu hồi vật tư tiêu hao trở lại kho.',
    ],
    'usersreset_password' => [
        'name' => 'Đặt lại mật khẩu người dùng',
    ],
    'usersprint' => [
        'name' => 'In danh mục tài sản của người dùng',
    ],
    'usersview_all' => [
        'name' => 'Xem tất cả người dùng',
        'note' => 'Khi Hỗ trợ đa đơn vị/cơ quan được bật, cho phép người dùng xem danh sách người dùng trên tất cả các đơn vị/cơ quan.',
    ],
    'statuslabels' => [
        'name' => 'Nhãn trạng thái',
        'note' => 'Cấp quyền truy cập vào phần Nhãn trạng thái.',
    ],
    'custom_fields' => [
        'name' => 'Trường tùy chỉnh',
        'note' => 'Cấp quyền truy cập vào phần Trường tùy chỉnh.',
    ],
    'custom_fieldsview' => [
        'name' => 'Xem trường tùy chỉnh',
    ],
    'custom_fieldscreate' => [
        'name' => 'Tạo mới trường tùy chỉnh',
    ],
    'custom_fieldsedit' => [
        'name' => 'Sửa trường tùy chỉnh',
    ],
    'custom_fieldsdelete' => [
        'name' => 'Xóa trường tùy chỉnh',
    ],
    'kitscheckout' => [
        'name' => 'Cấp phát gói cấu hình sẵn',
    ],
    'maintenances' => [
        'name' => 'Bảo trì tài sản',
        'note' => 'Cấp quyền truy cập vào phần Bảo trì tài sản.',
    ],
    'maintenancesview' => [
        'name' => 'Xem bảo trì tài sản',
    ],
    'maintenancescreate' => [
        'name' => 'Tạo mới bảo trì tài sản',
    ],
    'maintenancesedit' => [
        'name' => 'Sửa bảo trì tài sản',
    ],
    'maintenancesdelete' => [
        'name' => 'Xóa bảo trì tài sản',
    ],
    'self' => [
        'two_factor' => [
            'name' => 'Tự quản lý xác thực hai yếu tố (2FA)',
            'note' => 'Cho phép người dùng tự quản lý và thiết lập xác thực 2FA cho tài khoản của chính mình.',
        ],
        'profile' => [
            'name' => 'Tự quản lý hồ sơ cá nhân',
            'note' => 'Cho phép người dùng tự chỉnh sửa thông tin hồ sơ của chính mình.',
        ],
        'password' => [
            'name' => 'Tự đổi mật khẩu',
            'note' => 'Cho phép người dùng tự thay đổi mật khẩu của chính mình.',
        ],
        'api' => [
            'name' => 'Tự quản lý mã API cá nhân',
            'note' => 'Cho phép người dùng tự tạo và quản lý mã API cá nhân.',
        ],
        'checkout' => [
            'name' => 'Tự cấp phát tài sản',
            'note' => 'Cho phép người dùng tự cấp phát tài sản cho chính mình.',
        ],
        'checkin' => [
            'name' => 'Tự thu hồi tài sản',
            'note' => 'Cho phép người dùng tự thu hồi tài sản của chính mình.',
        ],
    ],
    'manufacturersfiles' => [
        'name' => 'Quản lý tệp nhà sản xuất',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của nhà sản xuất.',
    ],
    'kitsfiles' => [
        'name' => 'Quản lý tệp gói cấu hình sẵn',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của gói cấu hình sẵn.',
    ],
    'maintenancesfiles' => [
        'name' => 'Quản lý tệp bảo trì',
        'note' => 'Cho phép tải lên, tải xuống và xóa tệp đính kèm của bảo trì tài sản.',
    ],
];
