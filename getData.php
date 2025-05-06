<?php
$pdo = new PDO("mysql:host=192.168.1.138;dbname=parking", "pooh", "");
$stmt = $pdo->query("SELECT * FROM parking_cards WHERE is_qrscan = 1 AND is_ready = 1 ORDER BY id DESC LIMIT 1");
$card = $stmt->fetch();

// Return JSON response
header('Content-Type: application/json');

echo json_encode($card ?? 0);
?>
