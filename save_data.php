<?php
// اتصال به دیتابیس
require_once 'config.php';

// دریافت اطلاعات ارسال شده از برنامه به صورت JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// اگر داده‌ای دریافت نشده بود یا ساختار درستی نداشت
if (!$data || !isset($data['units']) || !isset($data['settings']) || !isset($data['periodInfo'])) {
    http_response_code(400); // Bad Request
    die(json_encode(['success' => false, 'message' => 'داده ورودی نامعتبر است.']));
}

// شروع یک تراکنش (Transaction) برای اطمینان از ذخیره کامل یا عدم ذخیره هیچ‌کدام از اطلاعات
$conn->begin_transaction();

try {
    // ۱. ذخیره اطلاعات واحدها
    $stmt_units = $conn->prepare("UPDATE units SET prev_reading = ?, current_reading = ? WHERE id = ?");
    foreach ($data['units'] as $unit) {
        $prev = $unit['prev'] ?? 0;
        $current = $unit['current'] ?? 0;
        $id = $unit['id'];
        $stmt_units->bind_param("ddi", $prev, $current, $id);
        $stmt_units->execute();
    }
    $stmt_units->close();

    // ۲. ذخیره تنظیمات
    $stmt_settings = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    
    // ذخیره حالت محاسبه
    $calcMode = $data['settings']['calculationMode'];
    $key_calc = 'calculationMode';
    $stmt_settings->bind_param("ss", $calcMode, $key_calc);
    $stmt_settings->execute();

    // ذخیره پلکان‌های دستی (با تبدیل به JSON)
    $manualTiers = json_encode($data['settings']['manualTiers']);
    $key_manual = 'manualTiers';
    $stmt_settings->bind_param("ss", $manualTiers, $key_manual);
    $stmt_settings->execute();

    // ذخیره پلکان‌های خودکار (با تبدیل به JSON)
    $autoTiers = json_encode($data['settings']['autoTiers']);
    $key_auto = 'autoTiers';
    $stmt_settings->bind_param("ss", $autoTiers, $key_auto);
    $stmt_settings->execute();
    
    $stmt_settings->close();

    // ۳. [جدید] ذخیره اطلاعات دوره فعلی
    $stmt_period = $conn->prepare("UPDATE period_info SET period_name = ?, bill_date = ?, total_bill = ? WHERE id = 1");
    $period_name = $data['periodInfo']['name'] ?? '';
    $bill_date = $data['periodInfo']['date'] ?? '';
    $total_bill = $data['periodInfo']['totalBill'] ?? 0;
    $stmt_period->bind_param("ssd", $period_name, $bill_date, $total_bill);
    $stmt_period->execute();
    $stmt_period->close();

    // اگر تمام دستورات بالا با موفقیت اجرا شدند، تراکنش را تایید کن
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت ذخیره شد.']);

} catch (Exception $e) {
    // اگر در هر مرحله‌ای خطا رخ داد، تمام تغییرات را به حالت اول برگردان
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره اطلاعات: ' . $e->getMessage()]);
}

$conn->close();
?>
