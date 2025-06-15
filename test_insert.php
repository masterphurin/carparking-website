<?php
$pdo = new PDO("mysql:host=192.168.1.138;dbname=parking", "pooh", "");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $stmt = $pdo->query("SELECT slot_number FROM parking_slots WHERE is_occupied = 0 ORDER BY RAND() LIMIT 1");
    $slot = $stmt->fetch();

    if (!$slot) {
        echo "❌ ไม่มีช่องว่างสำหรับทดสอบ";
        exit;
    }

    $slot_number = $slot['slot_number'];
    $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24); 
    $license_plate = "ทดสอบ" . rand(1000, 9999); 
    $expire_at = date("Y-m-d H:i:s", strtotime("+3 days")); 
    $entry_time = gmdate("Y-m-d H:i:s", time() + 7 * 3600);

    $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);

    $pdo->prepare("UPDATE parking_slots SET is_occupied = 1 WHERE slot_number = ?")->execute([$slot_number]);

    $result_message = "✅ เพิ่มข้อมูลทดสอบสำเร็จ<br>Slot: $slot_number<br>Card ID: $card_id<br>ทะเบียน: $license_plate";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลอัตโนมัติ</title>
</head>
<body>

<h2>ทดสอบการเพิ่มข้อมูลบัตรจอดรถ</h2>

<form method="POST" action="test_insert.php">
    <button type="submit" name="submit">เพิ่มข้อมูลอัตโนมัติ</button>
</form>

<?php if (isset($result_message)) { echo "<div id='result'>$result_message</div>"; } ?>

</body>
</html>
