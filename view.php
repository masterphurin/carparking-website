<?php
/**
 * ระบบจอดรถอัจฉริยะ - หน้าดูข้อมูล
 * Smart Parking System - View Page
 */

require_once __DIR__ . '/services/ParkingService.php';

$parkingService = new ParkingService();

// จัดการการกดปุ่ม (ลบข้อมูล)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = $_POST['card_id'];
    
    try {
        $parkingService->deleteCard($card_id);
        $show_success = true;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $show_success = false;
    }
} else {
    $show_success = false;
}

// ตรวจสอบ card_id
$card = null;
$parking_data = null;

if (isset($_GET['card_id']) && !$show_success) {
    $card_id = $_GET['card_id'];
    
    try {
        $card = $parkingService->getCardById($card_id);
        
        if ($card) {
            $parking_data = $parkingService->calculateParkingFee($card['entry_time']);
            
            // อัปเดต QR สแกนแล้ว
            $parkingService->updateQRScanStatus($card_id);

            // อัปเดตสถานะช่องจอดรถ
            $parkingService->updateSlotStatus($card_id);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการจอดรถ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/view.css">
</head>
<body class="p-4 md:p-8">
    <div class="container mx-auto max-w-4xl">
        <?php if ($show_success): ?>
            <!-- Success message after form submission -->
            <div class="card bg-white bg-opacity-95 rounded-2xl p-8 text-center">
                <div class="success-animation mb-6">
                    <svg class="w-24 h-24 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">✅ เปิดไม้กั้นเรียบร้อย</h2>
                <p class="text-xl text-gray-600 mb-6">ขอบคุณที่ใช้บริการ ขับขี่ปลอดภัย</p>
                
                <div class="flex justify-center items-end mb-8">
                    <div class="barrier-base"></div>
                    <div class="barrier open ml-4 mr-8"></div>
                    <svg class="w-16 h-16 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                </div>
                
                <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                    กลับสู่หน้าหลัก
                </a>
            </div>
        <?php else: ?>
            <!-- Card details display -->
            <header class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white">รายละเอียดการจอดรถ</h1>
                <p class="text-blue-100 mt-2">ตรวจสอบข้อมูลและชำระค่าบริการ</p>
            </header>
            
            <div class="card bg-white bg-opacity-95 rounded-2xl overflow-hidden">
                <div class="bg-blue-600 py-4 px-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-white">
                            <?php if ($card): ?>
                                ข้อมูลช่องจอด #<?php echo htmlspecialchars($card['slot_number']); ?>
                            <?php else: ?>
                                บัตรจอดรถ
                            <?php endif; ?>
                        </h2>
                        <div class="bg-yellow-400 text-blue-900 font-bold py-1 px-3 rounded-full text-sm">
                            รถยนต์
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <?php if ($card): ?>
                        <!-- แสดง countdown ช่วงจอดฟรี -->
                        <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <strong class="text-green-800">⏳ เวลานับถอยหลังช่วงจอดฟรี (3 ชม.):</strong>
                            </div>
                            <div id="countdown" class="countdown text-center"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">หมายเลขบัตรจอด</p>
                                    <p class="text-gray-800 font-medium text-lg">
                                        <?php echo htmlspecialchars($card['card_id']); ?>
                                    </p>
                                </div>
                                
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">เวลาเข้า</p>
                                    <p class="text-gray-800 font-medium text-lg">
                                        <?php echo $parking_data['entry_time']->format('d/m/Y H:i:s'); ?>
                                    </p>
                                </div>
                                
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">เวลาปัจจุบัน</p>
                                    <p class="text-gray-800 font-medium text-lg" id="current-time">
                                        <?php echo $parking_data['current_time']->format('d/m/Y H:i:s'); ?>
                                    </p>
                                </div>
                                
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">ระยะเวลาจอด</p>
                                    <p class="text-gray-800 font-medium text-lg">
                                        <?php 
                                        $display_hours = $parking_data['interval']->h + ($parking_data['interval']->days * 24);
                                        $minutes = $parking_data['interval']->i;
                                        echo "$display_hours ชั่วโมง $minutes นาที";
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div>
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">อัตราค่าบริการ</p>
                                    <p class="text-gray-800 font-medium text-lg">20 บาท / ชั่วโมง</p>
                                </div>
                                
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">จอดรวม</p>
                                    <p class="text-gray-800 font-medium text-lg"><?php echo $parking_data['total_hours']; ?> ชั่วโมง</p>
                                </div>
                                
                                <div class="border-b border-gray-200 py-4 info-row">
                                    <p class="text-gray-500 text-sm">ค่าบริการทั้งหมด</p>
                                    <p class="text-blue-600 font-bold text-2xl">
                                        <?php echo number_format($parking_data['total_price']); ?> บาท
                                    </p>
                                </div>
                                
                                <div class="py-4">
                                    <p class="text-gray-500 text-sm mb-2">สถานะ</p>
                                    <div class="bg-green-100 text-green-800 py-2 px-4 rounded-lg inline-flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        พร้อมชำระเงิน
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <form method="POST" action="" id="gateForm">
                                <input type="hidden" name="card_id" value="<?php echo htmlspecialchars($card['card_id']); ?>">
                                <button type="submit" id="gateButton" class="btn-primary w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl text-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span id="button-text">🚗 เปิดไม้กั้น</span>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="py-8 text-center">
                            <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบข้อมูลบัตรจอดรถ</h3>
                            <p class="text-gray-600 mb-6">บัตรจอดรถไม่ถูกต้องหรือถูกใช้งานไปแล้ว</p>
                            <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                กลับสู่หน้าหลัก
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-blue-100">© 2023 ระบบจอดรถอัจฉริยะ | <span id="footer-time"></span></p>
            </div>
            
            <!-- Car animation -->
            <div class="car-animation hidden md:block">
                <svg class="w-20 h-20 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($card && !$show_success): ?>
        // Countdown functionality
        const freeUntil = new Date(<?php echo $parking_data['free_until_timestamp']; ?>);
        const countdownEl = document.getElementById('countdown');
        const gateBtn = document.getElementById('button-text');

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = freeUntil - now;

            if (distance > 0) {
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);
                countdownEl.innerHTML = h + ' ชม. ' + m + ' นาที ' + s + ' วินาที';
                countdownEl.className = 'countdown';
                gateBtn.innerHTML = '🚗 เปิดไม้กั้น';
            } else {
                countdownEl.innerHTML = 'หมดช่วงจอดฟรี';
                countdownEl.className = 'countdown expired';
                gateBtn.innerHTML = '💳 ชำระเงินเพื่อเปิดไม้กั้น';
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>

        // Update current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            
            const timeString = now.toLocaleDateString('th-TH', options);
            
            if (document.getElementById('current-time')) {
                document.getElementById('current-time').textContent = timeString;
            }
            
            if (document.getElementById('footer-time')) {
                document.getElementById('footer-time').textContent = timeString;
            }
        }
        
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>