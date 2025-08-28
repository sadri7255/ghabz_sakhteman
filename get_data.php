<?php
// اتصال به دیتابیس با استفاده از فایل تنظیمات
require_once 'config.php';

// ساختار اولیه برای نگهداری اطلاعات
$state = [
    'units' => [],
    'settings' => [
        'calculationMode' => 'manual',
        'manualTiers' => [],
        'autoTiers' => []
    ],
    'periodInfo' => [ 'name' => '', 'date' => '', 'totalBill' => 0 ]
];

// ۱. خواندن اطلاعات واحدها از جدول units
$result_units = $conn->query("SELECT id, unit_name, prev_reading, current_reading FROM units ORDER BY id");
if ($result_units) {
    while ($row = $result_units->fetch_assoc()) {
        $state['units'][] = [
            'id' => (int)$row['id'],
            'name' => $row['unit_name'],
            'prev' => (float)$row['prev_reading'],
            'current' => (float)$row['current_reading']
        ];
    }
}

// ۲. خواندن تنظیمات از جدول settings
$result_settings = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result_settings) {
    while ($row = $result_settings->fetch_assoc()) {
        if ($row['setting_key'] === 'calculationMode') {
            $state['settings']['calculationMode'] = $row['setting_value'];
        } else {
            // مقادیر JSON را به آرایه تبدیل می‌کنیم
            $state['settings'][$row['setting_key']] = json_decode($row['setting_value'], true);
        }
    }
}

// ارسال اطلاعات نهایی به صورت JSON
echo json_encode($state);

// بستن اتصال دیتابیس
$conn->close();
?>