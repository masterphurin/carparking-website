<?php
// รับค่าจาก GET
$card_id = $_GET['slot_number'] ?? null;

if ($card_id === null) {
    die("Slot number is required.");
}

try {
    $pdo = new PDO("mysql:host=192.168.1.138;dbname=parking", "pooh", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare("
        UPDATE parking_cards 
        SET is_ready = 0
        WHERE is_qrscan = 1 AND is_ready = 1 AND slot_number = :slot_number 
    ");
    $stmt->execute(['slot_number' => $card_id]);

    echo "Update successful.";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
