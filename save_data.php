<?php
// اتصال به دیتابیس
require_once 'config.php';

// دریافت اطلاعات ارسال شده از برنامه به صورت JSON
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// اگر داده‌ای دریافت شده بود
if ($data) {
    // ۱. ذخیره اطلاعات واحدها
    // از prepared statements برای امنیت بیشتر استفاده می‌شود
    $stmt_units = $conn->prepare("UPDATE units SET prev_reading = ?, current_reading = ? WHERE id = ?");
    foreach ($data['units'] as $unit) {
        $stmt_units->bind_param("ddi", $unit['prev'], $unit['current'], $unit['id']);
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

    // ذخیره پلکان‌های دستی
    $manualTiers = json_encode($data['settings']['manualTiers']);
    $key_manual = 'manualTiers';
    $stmt_settings->bind_param("ss", $manualTiers, $key_manual);
    $stmt_settings->execute();

    // ذخیره پلکان‌های خودکار
    $autoTiers = json_encode($data['settings']['autoTiers']);
    $key_auto = 'autoTiers';
    $stmt_settings->bind_param("ss", $autoTiers, $key_auto);
    $stmt_settings->execute();
    
    $stmt_settings->close();

    echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت ذخیره شد.']);
} else {
    echo json_encode(['success' => false, 'message' => 'داده‌ای برای ذخیره دریافت نشد.']);
}

$conn->close();
?>
