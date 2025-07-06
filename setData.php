<?php
/**
 * setData.php - อัปเดตสถานะบัตรจอดรถ
 */

require_once __DIR__ . '/config/DatabaseConnection.php';

// รับค่าจาก GET
$card_id = $_GET['slot_number'] ?? null;

if ($card_id === null) {
    die("Slot number is required.");
}

try {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("
        UPDATE parking_cards 
        SET is_ready = 0
        WHERE is_qrscan = 1 AND is_ready = 1 AND slot_number = :slot_number 
    ");
    $stmt->execute(['slot_number' => $card_id]);

    echo "Update successful.";
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
