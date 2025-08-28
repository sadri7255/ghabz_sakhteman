<?php
// این فایل جدید برای ذخیره یک دوره در جدول تاریخچه است

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['period_name'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'اطلاعات ورودی برای ذخیره تاریخچه ناقص است.']));
}

$period_name = $data['period_name'];
$bill_date = $data['bill_date'] ?? '';
$total_bill = $data['total_bill'] ?? 0;
$total_consumption = $data['total_consumption'] ?? 0;
$units_data = $data['units_data'] ?? '[]';

// برای جلوگیری از ذخیره دوره‌های تکراری، از دستور INSERT ... ON DUPLICATE KEY UPDATE استفاده می‌کنیم
// این دستور چک می‌کند اگر دوره‌ای با همین نام وجود داشت، آن را آپدیت می‌کند و در غیر این صورت، یک دوره جدید اضافه می‌کند
// برای این کار باید ستون period_name در جدول history به عنوان UNIQUE تعریف شده باشد
$stmt = $conn->prepare("
    INSERT INTO history (period_name, bill_date, total_bill, total_consumption, units_data) 
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    bill_date = VALUES(bill_date), 
    total_bill = VALUES(total_bill), 
    total_consumption = VALUES(total_consumption), 
    units_data = VALUES(units_data)
");

$stmt->bind_param("ssdds", $period_name, $bill_date, $total_bill, $total_consumption, $units_data);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'دوره با موفقیت در تاریخچه ذخیره/به‌روزرسانی شد.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره تاریخچه: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
