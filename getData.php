<?php
/**
 * getData.php - ดึงข้อมูลบัตรจอดรถที่พร้อมใช้งาน
 */

require_once __DIR__ . '/config/DatabaseConnection.php';

try {
    $pdo = getDatabase();
    
    $stmt = $pdo->query("SELECT * FROM parking_cards WHERE is_qrscan = 1 AND is_ready = 1 ORDER BY id DESC LIMIT 1");
    $card = $stmt->fetch();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($card ?? 0);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
