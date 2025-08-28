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
    $stmt_units = $conn->prepare("REPLACE INTO units (id, unit_name, prev_reading, current_reading) VALUES (?, ?, ?, ?)");
    foreach ($data['units'] as $unit) {
        $stmt_units->bind_param("isdd", $unit['id'], $unit['name'], $unit['prev'], $unit['current']);
        $stmt_units->execute();
    }
    $stmt_units->close();

    // ۲. ذخیره تنظیمات
    $stmt_settings = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    
    // ذخیره حالت محاسبه
    $calcMode = $data['settings']['calculationMode'];
    $key_calc = 'calculationMode';
    $stmt_settings->bind_param("ss", $key_calc, $calcMode);
    $stmt_settings->execute();

    // ذخیره پلکان‌های دستی
    $manualTiers = json_encode($data['settings']['manualTiers']);
    $key_manual = 'manualTiers';
    $stmt_settings->bind_param("ss", $key_manual, $manualTiers);
    $stmt_settings->execute();

    // ذخیره پلکان‌های خودکار
    $autoTiers = json_encode($data['settings']['autoTiers']);
    $key_auto = 'autoTiers';
    $stmt_settings->bind_param("ss", $key_auto, $autoTiers);
    $stmt_settings->execute();
    
    $stmt_settings->close();

    echo json_encode(['success' => true, 'message' => 'اطلاعات با موفقیت ذخیره شد.']);
} else {
    echo json_encode(['success' => false, 'message' => 'داده‌ای برای ذخیره دریافت نشد.']);
}

$conn->close();
?>