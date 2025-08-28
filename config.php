<?php
// نمایش تمام خطاها برای اشکال‌زدایی آسان‌تر
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// [اصلاح شده] به جای یک آدرس خاص، به همه آدرس‌ها اجازه دسترسی داده می‌شود
// این کار اتصال برنامه شما از هر جایی (مثلا گیت‌هاب یا سیستم شخصی) به سرور را ممکن می‌سازد
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// این بخش برای هماهنگی اولیه (preflight) بین مرورگر و سرور است
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- اطلاعات اتصال به دیتابیس شما ---
// !!! این اطلاعات را محرمانه نگه دارید
define('DB_HOST', 'sql12.freesqldatabase.com');
define('DB_USER', 'sql12796490');
define('DB_PASS', 'pd5mwU31A5');
define('DB_NAME', 'sql12796490');

// ایجاد اتصال به دیتابیس
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// بررسی موفقیت‌آمیز بودن اتصال
if ($conn->connect_error) {
    // در صورت بروز خطا، پیام خطا را به صورت JSON برمی‌گرداند و اجرا را متوقف می‌کند
    http_response_code(500); // Internal Server Error
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// تنظیم انکودینگ ارتباط با دیتابیس برای پشتیبانی کامل از زبان فارسی
$conn->set_charset("utf8mb4");

?>
