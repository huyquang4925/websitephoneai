<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();

deny_access('Admin chỉ được xem và xóa tài khoản, không được sửa.');
