<?php
// Thông tin kết nối mặc định của XAMPP.
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'phone_store';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Không thể kết nối cơ sở dữ liệu: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
