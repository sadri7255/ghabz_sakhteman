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
} else {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Error fetching units: ' . $conn->error]));
}

// ۲. خواندن تنظیمات از جدول settings
$result_settings = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result_settings) {
    while ($row = $result_settings->fetch_assoc()) {
        if ($row['setting_key'] === 'calculationMode') {
            $state['settings']['calculationMode'] = $row['setting_value'];
        } else {
            // مقادیر JSON را به آرایه تبدیل می‌کنیم
            $decoded_value = json_decode($row['setting_value'], true);
            // اگر تبدیل موفقیت آمیز نبود، یک آرایه خالی قرار می‌دهیم
            $state['settings'][$row['setting_key']] = $decoded_value !== null ? $decoded_value : [];
        }
    }
} else {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Error fetching settings: ' . $conn->error]));
}

// ۳. [جدید] خواندن اطلاعات دوره فعلی از جدول period_info
// فرض شده که همیشه یک ردیف با id=1 برای نگهداری اطلاعات دوره جاری وجود دارد
$result_period = $conn->query("SELECT period_name, bill_date, total_bill FROM period_info WHERE id = 1");
if ($result_period && $result_period->num_rows > 0) {
    $period_row = $result_period->fetch_assoc();
    $state['periodInfo'] = [
        'name' => $period_row['period_name'],
        'date' => $period_row['bill_date'],
        'totalBill' => (float)$period_row['total_bill']
    ];
}
// اگر خطایی در این بخش رخ دهد، از مقادیر پیش‌فرض استفاده می‌شود و برنامه متوقف نمی‌شود

// ارسال اطلاعات نهایی به صورت JSON
echo json_encode($state);

// بستن اتصال دیتابیس
$conn->close();
?>
