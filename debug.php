<?php
/**
 * Debug file สำหรับตรวจสอบปัญหา QR Code
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug QR Code System</h2>";

// 1. ตรวจสอบการโหลด Service files
echo "<h3>1. ตรวจสอบการโหลดไฟล์</h3>";
try {
    require_once __DIR__ . '/services/ParkingService.php';
    echo "✅ ParkingService.php โหลดสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ ParkingService.php โหลดไม่สำเร็จ: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/services/QRCodeService.php';
    echo "✅ QRCodeService.php โหลดสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ QRCodeService.php โหลดไม่สำเร็จ: " . $e->getMessage() . "<br>";
}

// 2. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>2. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h3>";
try {
    $parkingService = new ParkingService();
    echo "✅ ParkingService สร้างสำเร็จ<br>";
} catch (Exception $e) {
    echo "❌ ParkingService สร้างไม่สำเร็จ: " . $e->getMessage() . "<br>";
}

// 3. ตรวจสอบข้อมูลในฐานข้อมูล
echo "<h3>3. ตรวจสอบข้อมูลในฐานข้อมูล</h3>";
try {
    $parkingService = new ParkingService();
    $card = $parkingService->getUnscannedCard();
    
    if ($card) {
        echo "✅ พบบัตรที่ยังไม่ได้สแกน: " . $card['card_id'] . "<br>";
        echo "- License Plate: " . $card['license_plate'] . "<br>";
        echo "- Entry Time: " . $card['entry_time'] . "<br>";
        echo "- Slot Number: " . $card['slot_number'] . "<br>";
    } else {
        echo "⚠️ ไม่พบบัตรที่ยังไม่ได้สแกน<br>";
    }
} catch (Exception $e) {
    echo "❌ ข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage() . "<br>";
}

// 4. ตรวจสอบการสร้าง QR Code
echo "<h3>4. ตรวจสอบการสร้าง QR Code</h3>";
try {
    $test_card_id = "TEST123456789";
    $qr_code = QRCodeService::generateParkingQRCode($test_card_id);
    
    if ($qr_code) {
        echo "✅ QR Code สร้างสำเร็จ (length: " . strlen($qr_code) . ")<br>";
        echo "<img src='data:image/png;base64,$qr_code' alt='Test QR Code' width='200'><br>";
    } else {
        echo "❌ QR Code สร้างไม่สำเร็จ<br>";
    }
} catch (Exception $e) {
    echo "❌ ข้อผิดพลาดในการสร้าง QR Code: " . $e->getMessage() . "<br>";
}

// 5. ตรวจสอบ composer autoload
echo "<h3>5. ตรวจสอบ Composer Dependencies</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ vendor/autoload.php มีอยู่<br>";
    
    // ตรวจสอบ QR Code library
    if (class_exists('Endroid\QrCode\Builder\Builder')) {
        echo "✅ Endroid QR Code library โหลดสำเร็จ<br>";
    } else {
        echo "❌ Endroid QR Code library โหลดไม่สำเร็จ<br>";
    }
} else {
    echo "❌ vendor/autoload.php ไม่มีอยู่<br>";
}

// 6. ทดสอบ API endpoint
echo "<h3>6. ทดสอบ API Endpoint</h3>";
echo "<a href='index.php?fetch=1' target='_blank'>คลิกเพื่อทดสอบ API</a><br>";

?>
