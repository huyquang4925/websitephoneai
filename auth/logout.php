<?php
require_once __DIR__ . '/../config/config.php';

unset($_SESSION['user_id'], $_SESSION['full_name'], $_SESSION['role']);
set_flash('success', 'Đã đăng xuất.');
redirect('/index.php');
