<?php
// این هدر به فایل‌های شما که روی گیت‌هاب هستند اجازه می‌دهد به این سرور دسترسی داشته باشند
// !!! مهم: حتما آدرس گیت‌هاب خود را جایگزین کنید
header("Access-Control-Allow-Origin: https://sadri7255.github.io");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// این بخش برای هماهنگی اولیه بین مرورگر و سرور است
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- اطلاعات اتصال به دیتابیس شما ---
define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12796490');
define('DB_PASS', 'pd5mwU31A5');
define('DB_NAME', 'sql12796490');

// ایجاد اتصال به دیتابیس
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// بررسی موفقیت‌آمیز بودن اتصال
if ($conn->connect_error) {
    // در صورت بروز خطا، پیام خطا را به صورت JSON برمی‌گرداند
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// تنظیم انکودینگ ارتباط با دیتابیس برای پشتیبانی از زبان فارسی
$conn->set_charset("utf8mb4");
?>