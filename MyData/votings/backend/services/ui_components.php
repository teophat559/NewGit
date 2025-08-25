<?php
// UI Components service - Quản lý components giao diện nâng cao
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class UIComponentsService {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
    }

    // ==================== FORM GENERATION ====================

    public function generateForm($formType, $data = [], $options = []) {
        try {
            switch ($formType) {
                case 'user_registration':
                    return $this->generateUserRegistrationForm($data, $options);

                case 'user_profile':
                    return $this->generateUserProfileForm($data, $options);

                case 'contest_creation':
                    return $this->generateContestCreationForm($data, $options);

                case 'contestant_registration':
                    return $this->generateContestantRegistrationForm($data, $options);

                case 'notification_template':
                    return $this->generateNotificationTemplateForm($data, $options);

                case 'search_filter':
                    return $this->generateSearchFilterForm($data, $options);

                default:
                    return ['success' => false, 'message' => 'Loại form không được hỗ trợ'];
            }

        } catch (Exception $e) {
            error_log("Form generation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo form: ' . $e->getMessage()];
        }
    }

    private function generateUserRegistrationForm($data = [], $options = []) {
        $form = [
            'id' => 'user-registration-form',
            'action' => '/backend/auth/register',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'username',
                    'label' => 'Tên đăng nhập',
                    'placeholder' => 'Nhập tên đăng nhập',
                    'required' => true,
                    'validation' => [
                        'min_length' => 3,
                        'max_length' => 50,
                        'pattern' => '^[a-zA-Z0-9_]+$'
                    ],
                    'value' => $data['username'] ?? '',
                    'error' => $data['errors']['username'] ?? null
                ],
                [
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'Email',
                    'placeholder' => 'Nhập email',
                    'required' => true,
                    'validation' => [
                        'type' => 'email'
                    ],
                    'value' => $data['email'] ?? '',
                    'error' => $data['errors']['email'] ?? null
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => 'Mật khẩu',
                    'placeholder' => 'Nhập mật khẩu',
                    'required' => true,
                    'validation' => [
                        'min_length' => 6,
                        'max_length' => 100
                    ],
                    'error' => $data['errors']['password'] ?? null
                ],
                [
                    'type' => 'password',
                    'name' => 'password_confirm',
                    'label' => 'Xác nhận mật khẩu',
                    'placeholder' => 'Nhập lại mật khẩu',
                    'required' => true,
                    'validation' => [
                        'match' => 'password'
                    ],
                    'error' => $data['errors']['password_confirm'] ?? null
                ],
                [
                    'type' => 'text',
                    'name' => 'full_name',
                    'label' => 'Họ và tên',
                    'placeholder' => 'Nhập họ và tên đầy đủ',
                    'required' => false,
                    'validation' => [
                        'max_length' => 100
                    ],
                    'value' => $data['full_name'] ?? '',
                    'error' => $data['errors']['full_name'] ?? null
                ],
                [
                    'type' => 'tel',
                    'name' => 'phone',
                    'label' => 'Số điện thoại',
                    'placeholder' => 'Nhập số điện thoại',
                    'required' => false,
                    'validation' => [
                        'pattern' => '^[0-9+\-\s()]+$'
                    ],
                    'value' => $data['phone'] ?? '',
                    'error' => $data['errors']['phone'] ?? null
                ],
                [
                    'type' => 'file',
                    'name' => 'avatar',
                    'label' => 'Ảnh đại diện',
                    'required' => false,
                    'accept' => 'image/*',
                    'validation' => [
                        'max_size' => '5MB',
                        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
                    ],
                    'error' => $data['errors']['avatar'] ?? null
                ]
            ],
            'buttons' => [
                [
                    'type' => 'submit',
                    'text' => 'Đăng ký',
                    'class' => 'btn btn-primary',
                    'icon' => 'fas fa-user-plus'
                ],
                [
                    'type' => 'reset',
                    'text' => 'Làm mới',
                    'class' => 'btn btn-secondary',
                    'icon' => 'fas fa-redo'
                ]
            ],
            'options' => [
                'show_labels' => true,
                'show_placeholders' => true,
                'show_validation' => true,
                'auto_focus' => true,
                'responsive' => true
            ]
        ];

        return [
            'success' => true,
            'data' => $form
        ];
    }

    private function generateUserProfileForm($data = [], $options = []) {
        $form = [
            'id' => 'user-profile-form',
            'action' => '/backend/user/update-profile',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'full_name',
                    'label' => 'Họ và tên',
                    'placeholder' => 'Nhập họ và tên đầy đủ',
                    'required' => false,
                    'validation' => [
                        'max_length' => 100
                    ],
                    'value' => $data['full_name'] ?? '',
                    'error' => $data['errors']['full_name'] ?? null
                ],
                [
                    'type' => 'tel',
                    'name' => 'phone',
                    'label' => 'Số điện thoại',
                    'placeholder' => 'Nhập số điện thoại',
                    'required' => false,
                    'validation' => [
                        'pattern' => '^[0-9+\-\s()]+$'
                    ],
                    'value' => $data['phone'] ?? '',
                    'error' => $data['errors']['phone'] ?? null
                ],
                [
                    'type' => 'file',
                    'name' => 'avatar',
                    'label' => 'Ảnh đại diện',
                    'required' => false,
                    'accept' => 'image/*',
                    'validation' => [
                        'max_size' => '5MB',
                        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
                    ],
                    'error' => $data['errors']['avatar'] ?? null
                ],
                [
                    'type' => 'textarea',
                    'name' => 'bio',
                    'label' => 'Giới thiệu',
                    'placeholder' => 'Viết giới thiệu về bản thân',
                    'required' => false,
                    'rows' => 4,
                    'validation' => [
                        'max_length' => 500
                    ],
                    'value' => $data['bio'] ?? '',
                    'error' => $data['errors']['bio'] ?? null
                ]
            ],
            'buttons' => [
                [
                    'type' => 'submit',
                    'text' => 'Cập nhật',
                    'class' => 'btn btn-primary',
                    'icon' => 'fas fa-save'
                ],
                [
                    'type' => 'button',
                    'text' => 'Hủy',
                    'class' => 'btn btn-secondary',
                    'icon' => 'fas fa-times',
                    'onclick' => 'closeModal()'
                ]
            ]
        ];

        return [
            'success' => true,
            'data' => $form
        ];
    }

    private function generateContestCreationForm($data = [], $options = []) {
        $form = [
            'id' => 'contest-creation-form',
            'action' => '/backend/contests/create',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => 'Tên cuộc thi',
                    'placeholder' => 'Nhập tên cuộc thi',
                    'required' => true,
                    'validation' => [
                        'min_length' => 5,
                        'max_length' => 200
                    ],
                    'value' => $data['title'] ?? '',
                    'error' => $data['errors']['title'] ?? null
                ],
                [
                    'type' => 'textarea',
                    'name' => 'description',
                    'label' => 'Mô tả',
                    'placeholder' => 'Mô tả chi tiết về cuộc thi',
                    'required' => true,
                    'rows' => 5,
                    'validation' => [
                        'min_length' => 20,
                        'max_length' => 2000
                    ],
                    'value' => $data['description'] ?? '',
                    'error' => $data['errors']['description'] ?? null
                ],
                [
                    'type' => 'date',
                    'name' => 'start_date',
                    'label' => 'Ngày bắt đầu',
                    'required' => true,
                    'min' => date('Y-m-d'),
                    'value' => $data['start_date'] ?? '',
                    'error' => $data['errors']['start_date'] ?? null
                ],
                [
                    'type' => 'date',
                    'name' => 'end_date',
                    'label' => 'Ngày kết thúc',
                    'required' => true,
                    'min' => date('Y-m-d', strtotime('+1 day')),
                    'value' => $data['end_date'] ?? '',
                    'error' => $data['errors']['end_date'] ?? null
                ],
                [
                    'type' => 'number',
                    'name' => 'max_contestants',
                    'label' => 'Số thí sinh tối đa',
                    'placeholder' => 'Nhập số thí sinh tối đa',
                    'required' => true,
                    'min' => 1,
                    'max' => 1000,
                    'value' => $data['max_contestants'] ?? 100,
                    'error' => $data['errors']['max_contestants'] ?? null
                ],
                [
                    'type' => 'textarea',
                    'name' => 'voting_rules',
                    'label' => 'Quy tắc bình chọn',
                    'placeholder' => 'Mô tả quy tắc bình chọn',
                    'required' => false,
                    'rows' => 3,
                    'validation' => [
                        'max_length' => 1000
                    ],
                    'value' => $data['voting_rules'] ?? '',
                    'error' => $data['errors']['voting_rules'] ?? null
                ],
                [
                    'type' => 'textarea',
                    'name' => 'prizes',
                    'label' => 'Giải thưởng',
                    'placeholder' => 'Mô tả giải thưởng',
                    'required' => false,
                    'rows' => 3,
                    'validation' => [
                        'max_length' => 1000
                    ],
                    'value' => $data['prizes'] ?? '',
                    'error' => $data['errors']['prizes'] ?? null
                ],
                [
                    'type' => 'select',
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'required' => true,
                    'options' => [
                        ['value' => 'draft', 'text' => 'Nháp'],
                        ['value' => 'active', 'text' => 'Hoạt động'],
                        ['value' => 'voting', 'text' => 'Đang bình chọn'],
                        ['value' => 'ended', 'text' => 'Kết thúc']
                    ],
                    'value' => $data['status'] ?? 'draft',
                    'error' => $data['errors']['status'] ?? null
                ]
            ],
            'buttons' => [
                [
                    'type' => 'submit',
                    'text' => 'Tạo cuộc thi',
                    'class' => 'btn btn-primary',
                    'icon' => 'fas fa-plus'
                ],
                [
                    'type' => 'button',
                    'text' => 'Lưu nháp',
                    'class' => 'btn btn-secondary',
                    'icon' => 'fas fa-save',
                    'onclick' => 'saveDraft()'
                ],
                [
                    'type' => 'reset',
                    'text' => 'Làm mới',
                    'class' => 'btn btn-outline-secondary',
                    'icon' => 'fas fa-redo'
                ]
            ]
        ];

        return [
            'success' => true,
            'data' => $form
        ];
    }

    // ==================== TABLE GENERATION ====================

    public function generateTable($tableType, $data = [], $options = []) {
        try {
            switch ($tableType) {
                case 'users_list':
                    return $this->generateUsersTable($data, $options);

                case 'contests_list':
                    return $this->generateContestsTable($data, $options);

                case 'contestants_list':
                    return $this->generateContestantsTable($data, $options);

                case 'votes_list':
                    return $this->generateVotesTable($data, $options);

                case 'notifications_list':
                    return $this->generateNotificationsTable($data, $options);

                default:
                    return ['success' => false, 'message' => 'Loại bảng không được hỗ trợ'];
            }

        } catch (Exception $e) {
            error_log("Table generation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo bảng: ' . $e->getMessage()];
        }
    }

    private function generateUsersTable($data = [], $options = []) {
        $table = [
            'id' => 'users-table',
            'class' => 'table table-striped table-hover',
            'responsive' => true,
            'columns' => [
                [
                    'key' => 'checkbox',
                    'header' => '<input type="checkbox" id="select-all-users">',
                    'type' => 'checkbox',
                    'width' => '50px'
                ],
                [
                    'key' => 'id',
                    'header' => 'ID',
                    'sortable' => true,
                    'width' => '80px'
                ],
                [
                    'key' => 'avatar',
                    'header' => 'Ảnh',
                    'type' => 'image',
                    'width' => '60px'
                ],
                [
                    'key' => 'username',
                    'header' => 'Tên đăng nhập',
                    'sortable' => true,
                    'searchable' => true
                ],
                [
                    'key' => 'full_name',
                    'header' => 'Họ và tên',
                    'sortable' => true,
                    'searchable' => true
                ],
                [
                    'key' => 'email',
                    'header' => 'Email',
                    'sortable' => true,
                    'searchable' => true
                ],
                [
                    'key' => 'status',
                    'header' => 'Trạng thái',
                    'type' => 'badge',
                    'sortable' => true,
                    'filters' => [
                        'active' => 'Hoạt động',
                        'inactive' => 'Không hoạt động',
                        'deleted' => 'Đã xóa'
                    ]
                ],
                [
                    'key' => 'role',
                    'header' => 'Vai trò',
                    'type' => 'badge',
                    'sortable' => true,
                    'filters' => [
                        'user' => 'Người dùng',
                        'moderator' => 'Điều hành viên',
                        'admin' => 'Quản trị viên'
                    ]
                ],
                [
                    'key' => 'last_login',
                    'header' => 'Đăng nhập cuối',
                    'type' => 'datetime',
                    'sortable' => true
                ],
                [
                    'key' => 'actions',
                    'header' => 'Thao tác',
                    'type' => 'actions',
                    'width' => '150px',
                    'actions' => [
                        [
                            'type' => 'view',
                            'icon' => 'fas fa-eye',
                            'class' => 'btn btn-sm btn-info',
                            'onclick' => 'viewUser'
                        ],
                        [
                            'type' => 'edit',
                            'icon' => 'fas fa-edit',
                            'class' => 'btn btn-sm btn-warning',
                            'onclick' => 'editUser'
                        ],
                        [
                            'type' => 'delete',
                            'icon' => 'fas fa-trash',
                            'class' => 'btn btn-sm btn-danger',
                            'onclick' => 'deleteUser'
                        ]
                    ]
                ]
            ],
            'data' => $data['users'] ?? [],
            'pagination' => $data['pagination'] ?? null,
            'filters' => [
                'status' => ['active', 'inactive'],
                'role' => ['user', 'moderator', 'admin'],
                'search' => ''
            ],
            'options' => [
                'show_actions' => true,
                'show_checkboxes' => true,
                'show_filters' => true,
                'show_search' => true,
                'show_pagination' => true,
                'rows_per_page' => [10, 25, 50, 100],
                'default_sort' => 'id',
                'default_order' => 'desc'
            ]
        ];

        return [
            'success' => true,
            'data' => $table
        ];
    }

    // ==================== MODAL GENERATION ====================

    public function generateModal($modalType, $data = [], $options = []) {
        try {
            switch ($modalType) {
                case 'user_details':
                    return $this->generateUserDetailsModal($data, $options);

                case 'confirm_action':
                    return $this->generateConfirmActionModal($data, $options);

                case 'image_preview':
                    return $this->generateImagePreviewModal($data, $options);

                case 'bulk_actions':
                    return $this->generateBulkActionsModal($data, $options);

                default:
                    return ['success' => false, 'message' => 'Loại modal không được hỗ trợ'];
            }

        } catch (Exception $e) {
            error_log("Modal generation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo modal: ' . $e->getMessage()];
        }
    }

    private function generateUserDetailsModal($data = [], $options = []) {
        $modal = [
            'id' => 'user-details-modal',
            'title' => 'Chi tiết người dùng',
            'size' => 'lg',
            'content' => [
                'type' => 'tabs',
                'tabs' => [
                    [
                        'id' => 'basic-info',
                        'title' => 'Thông tin cơ bản',
                        'icon' => 'fas fa-user',
                        'content' => [
                            'type' => 'info_grid',
                            'fields' => [
                                ['label' => 'ID', 'value' => $data['id'] ?? ''],
                                ['label' => 'Tên đăng nhập', 'value' => $data['username'] ?? ''],
                                ['label' => 'Email', 'value' => $data['email'] ?? ''],
                                ['label' => 'Họ và tên', 'value' => $data['full_name'] ?? ''],
                                ['label' => 'Số điện thoại', 'value' => $data['phone'] ?? ''],
                                ['label' => 'Vai trò', 'value' => $this->getRoleLabel($data['role'] ?? '')],
                                ['label' => 'Trạng thái', 'value' => $this->getStatusLabel($data['status'] ?? '')],
                                ['label' => 'Ngày tạo', 'value' => $data['created_at'] ?? ''],
                                ['label' => 'Đăng nhập cuối', 'value' => $data['last_login'] ?? '']
                            ]
                        ]
                    ],
                    [
                        'id' => 'statistics',
                        'title' => 'Thống kê',
                        'icon' => 'fas fa-chart-bar',
                        'content' => [
                            'type' => 'stats_grid',
                            'stats' => $data['stats'] ?? []
                        ]
                    ],
                    [
                        'id' => 'activity',
                        'title' => 'Hoạt động',
                        'icon' => 'fas fa-history',
                        'content' => [
                            'type' => 'activity_list',
                            'activities' => $data['activities'] ?? []
                        ]
                    ]
                ]
            ],
            'footer' => [
                'buttons' => [
                    [
                        'type' => 'button',
                        'text' => 'Đóng',
                        'class' => 'btn btn-secondary',
                        'data_dismiss' => 'modal'
                    ],
                    [
                        'type' => 'button',
                        'text' => 'Chỉnh sửa',
                        'class' => 'btn btn-primary',
                        'onclick' => 'editUser'
                    ]
                ]
            ],
            'options' => [
                'backdrop' => true,
                'keyboard' => true,
                'focus' => true,
                'show' => false
            ]
        ];

        return [
            'success' => true,
            'data' => $modal
        ];
    }

    private function generateConfirmActionModal($data = [], $options = []) {
        $modal = [
            'id' => 'confirm-action-modal',
            'title' => $data['title'] ?? 'Xác nhận hành động',
            'size' => 'sm',
            'content' => [
                'type' => 'alert',
                'alert_type' => $data['alert_type'] ?? 'warning',
                'icon' => $data['icon'] ?? 'fas fa-exclamation-triangle',
                'message' => $data['message'] ?? 'Bạn có chắc chắn muốn thực hiện hành động này?'
            ],
            'footer' => [
                'buttons' => [
                    [
                        'type' => 'button',
                        'text' => 'Hủy',
                        'class' => 'btn btn-secondary',
                        'data_dismiss' => 'modal'
                    ],
                    [
                        'type' => 'button',
                        'text' => $data['confirm_text'] ?? 'Xác nhận',
                        'class' => 'btn btn-' . ($data['confirm_class'] ?? 'danger'),
                        'onclick' => $data['on_confirm'] ?? 'confirmAction'
                    ]
                ]
            ]
        ];

        return [
            'success' => true,
            'data' => $modal
        ];
    }

    // ==================== VALIDATION ====================

    public function validateFormData($formType, $data) {
        try {
            switch ($formType) {
                case 'user_registration':
                    return $this->validateUserRegistration($data);

                case 'user_profile':
                    return $this->validateUserProfile($data);

                case 'contest_creation':
                    return $this->validateContestCreation($data);

                default:
                    return ['valid' => false, 'message' => 'Loại form không được hỗ trợ'];
            }

        } catch (Exception $e) {
            error_log("Form validation failed: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Lỗi validation: ' . $e->getMessage()];
        }
    }

    private function validateUserRegistration($data) {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Tên đăng nhập không được để trống';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Tên đăng nhập không được quá 50 ký tự';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email không được để trống';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Mật khẩu không được để trống';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }

        // Password confirmation
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Mật khẩu xác nhận không khớp';
        }

        // Full name validation
        if (!empty($data['full_name']) && strlen($data['full_name']) > 100) {
            $errors['full_name'] = 'Họ và tên không được quá 100 ký tự';
        }

        // Phone validation
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function getRoleLabel($role) {
        $labels = [
            'user' => 'Người dùng',
            'moderator' => 'Điều hành viên',
            'admin' => 'Quản trị viên',
            'super_admin' => 'Quản trị viên cao cấp'
        ];

        return $labels[$role] ?? $role;
    }

    private function getStatusLabel($status) {
        $labels = [
            'active' => 'Hoạt động',
            'inactive' => 'Không hoạt động',
            'deleted' => 'Đã xóa',
            'pending' => 'Chờ xử lý',
            'suspended' => 'Tạm khóa'
        ];

        return $labels[$status] ?? $status;
    }

    public function generatePagination($total, $currentPage, $perPage, $urlPattern) {
        $totalPages = ceil($total / $perPage);

        if ($totalPages <= 1) {
            return null;
        }

        $pagination = [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'per_page' => $perPage,
            'pages' => [],
            'navigation' => []
        ];

        // Generate page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $pagination['pages'][] = [
                'number' => $i,
                'url' => str_replace('{page}', $i, $urlPattern),
                'active' => $i == $currentPage,
                'disabled' => false
            ];
        }

        // Previous button
        if ($currentPage > 1) {
            $pagination['navigation']['prev'] = [
                'url' => str_replace('{page}', $currentPage - 1, $urlPattern),
                'disabled' => false
            ];
        } else {
            $pagination['navigation']['prev'] = [
                'url' => '#',
                'disabled' => true
            ];
        }

        // Next button
        if ($currentPage < $totalPages) {
            $pagination['navigation']['next'] = [
                'url' => str_replace('{page}', $currentPage + 1, $urlPattern),
                'disabled' => false
            ];
        } else {
            $pagination['navigation']['next'] = [
                'url' => '#',
                'disabled' => true
            ];
        }

        return $pagination;
    }
}

// Helper function để sử dụng UI components service
function ui() {
    return new UIComponentsService();
}
?>
