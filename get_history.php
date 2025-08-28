<?php
// این فایل جدید برای خواندن تاریخچه دوره‌ها از دیتابیس است

require_once 'config.php';

$history = [];

// خواندن تمام دوره‌های ذخیره شده از جدول تاریخچه، به ترتیب نزولی (جدیدترین اول)
$sql = "SELECT id, period_name, bill_date, total_bill, total_consumption, units_data FROM history ORDER BY id DESC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'id' => (int)$row['id'],
            'period_name' => $row['period_name'],
            'bill_date' => $row['bill_date'],
            'total_bill' => (float)$row['total_bill'],
            'total_consumption' => (float)$row['total_consumption'],
            'units_data' => $row['units_data'] // داده‌های واحدها به صورت JSON خام ارسال می‌شود
        ];
    }
    echo json_encode($history);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در دریافت تاریخچه: ' . $conn->error]);
}

$conn->close();
?>
