<?php
/**
 * Test database connection
 */

try {
    require_once __DIR__ . '/config/DatabaseConnection.php';
    
    $pdo = getDatabase();
    
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ!\n";
    
    // ทดสอบการ query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM parking_cards");
    $result = $stmt->fetch();
    
    echo "📊 จำนวนบัตรจอดรถทั้งหมด: " . $result['count'] . "\n";
    
    // ทดสอบการ query สถิติ
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM parking_cards WHERE is_paid = 0");
    $active = $stmt->fetch()['active'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM parking_cards WHERE is_paid = 1");
    $completed = $stmt->fetch()['completed'];
    
    echo "🚗 บัตรที่กำลังจอดอยู่: " . $active . "\n";
    echo "✅ บัตรที่จ่ายแล้ว: " . $completed . "\n";
    
    // ทดสอบการ query ข้อมูลล่าสุด
    $stmt = $pdo->query("SELECT * FROM parking_cards ORDER BY entry_time DESC LIMIT 3");
    $recent = $stmt->fetchAll();
    
    echo "📝 ข้อมูล 3 รายการล่าสุด:\n";
    foreach ($recent as $card) {
        echo "  - " . substr($card['card_id'], 0, 12) . "... | " . $card['license_plate'] . " | " . $card['entry_time'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}
