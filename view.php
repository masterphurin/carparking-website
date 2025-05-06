<?php
date_default_timezone_set('Asia/Bangkok');
$pdo = new PDO("mysql:host=192.168.1.138;dbname=parking", "pooh", "");

// จัดการการกดปุ่ม (ลบข้อมูล)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = $_POST['card_id'];

    // ลบข้อมูลจากตาราง parking_cards
    $stmt = $pdo->prepare("DELETE FROM parking_cards WHERE card_id = ?");
    $stmt->execute([$card_id]);

    echo "<h2>✅ เปิดไม้กั้นเรียบร้อย ขอให้ท่านเดินทางอย่างสวัสดิภาพเด้อหำ</h2>";
    exit;
}

// ตรวจสอบ card_id
if (isset($_GET['card_id'])) {
    $card_id = $_GET['card_id'];
    $stmt = $pdo->prepare("SELECT * FROM parking_cards WHERE card_id = ?");
    $stmt->execute([$card_id]);
    $card = $stmt->fetch();

    if ($card) {
        $entry_time = new DateTime($card['entry_time']);
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('Asia/Bangkok'));

        $interval = $entry_time->diff($now);
        $hours = $interval->days * 24 + $interval->h + ($interval->i > 0 ? 1 : 0);
        $chargeable_hours = max(0, $hours - 3);
        $total_price = $chargeable_hours * 30;

        $free_until = clone $entry_time;
        $free_until->modify('+3 hours');
        $free_until_timestamp = $free_until->getTimestamp() * 1000;

        // อัปเดต QR สแกนแล้ว
        $stmt_update = $pdo->prepare("UPDATE parking_cards SET is_qrscan = 1, is_ready = 1 WHERE card_id = ?");
        $stmt_update->execute([$card_id]);

        // แสดงข้อมูล
        echo "<h2>ข้อมูลช่องจอด #" . htmlspecialchars($card['slot_number']) . "</h2>";
        echo "หมายเลขบัตรจอด: " . htmlspecialchars($card['card_id']) . "<br>";
        echo "เวลาเข้า (ไทย): " . $entry_time->format('d/m/Y H:i:s') . "<br>";
        echo "เวลาปัจจุบัน: " . $now->format('d/m/Y H:i:s') . "<br>";
        echo "จอดรวม: $hours ชั่วโมง<br>";
        echo "ค่าจอด: <strong>" . number_format($total_price) . " บาท</strong><br><br>";

        // countdown + ปุ่มเปิดไม้กั้น
        echo "<div><strong>⏳ เวลานับถอยหลังช่วงจอดฟรี (3 ชม.):</strong></div>";
        echo "<div id='countdown' style='font-size: 24px; font-weight: bold; color: green;'></div><br>";

        echo "
        <form method='POST' id='gateForm'>
            <input type='hidden' name='card_id' value='" . htmlspecialchars($card_id) . "'>
            <button id='gateButton' type='submit' style='padding: 10px 20px; font-size: 18px;'></button>
        </form>
        ";

        echo "
        <script>
            const freeUntil = new Date($free_until_timestamp);
            const now = new Date();
            const countdownEl = document.getElementById('countdown');
            const gateBtn = document.getElementById('gateButton');

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = freeUntil - now;

                if (distance > 0) {
                    const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((distance % (1000 * 60)) / 1000);
                    countdownEl.innerHTML = h + ' ชม. ' + m + ' นาที ' + s + ' วินาที';
                    countdownEl.style.color = 'green';
                    gateBtn.innerHTML = '🚗 เปิดไม้กั้น';
                } else {
                    countdownEl.innerHTML = 'หมดช่วงจอดฟรี';
                    countdownEl.style.color = 'red';
                    gateBtn.innerHTML = '💳 ชำระเงินเพื่อเปิดไม้กั้น';
                }
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>
        ";
    } else {
        echo "❌ ไม่พบข้อมูลบัตรที่ระบุ";
    }
} else {
    echo "❌ ไม่มีข้อมูลบัตรที่ต้องการ";
}
?>
