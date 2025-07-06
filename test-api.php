<?php
/**
 * Test API endpoint
 */
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // โหลด Services
    require_once __DIR__ . '/services/ParkingService.php';
    require_once __DIR__ . '/services/QRCodeService.php';

    echo "Loading services...\n";
    
    $parkingService = new ParkingService();
    $card = $parkingService->getUnscannedCard();

    echo "Getting card data...\n";
    
    if (!$card) {
        $response = [
            'status' => 'no_data', 
            'message' => 'ยินดีต้อนรับ'
        ];
    } else {
        echo "Generating QR code...\n";
        
        $card_id = $card['card_id'];
        $qr_code = QRCodeService::generateParkingQRCode($card_id);

        $response = [
            'status' => 'success', 
            'qr_code' => $qr_code,
            'card_id' => $card_id
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'status' => 'error', 
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    echo json_encode($response);
}
?>
