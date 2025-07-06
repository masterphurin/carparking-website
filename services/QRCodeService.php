<?php
/**
 * QR Code Service
 * เซอร์วิสสำหรับการสร้าง QR Code
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeService {
    
    /**
     * สร้าง QR Code
     * @param string $data ข้อมูลที่ต้องการสร้าง QR Code
     * @param int $size ขนาดของ QR Code (default: 300)
     * @return string Base64 encoded QR Code image
     */
    public static function generateQRCode($data, $size = 300) {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($data)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size($size)
                ->margin(10)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->build();

            return base64_encode($result->getString());
        } catch (Exception $e) {
            throw new Exception("ไม่สามารถสร้าง QR Code ได้: " . $e->getMessage());
        }
    }
    
    /**
     * สร้าง QR Code สำหรับ URL
     * @param string $card_id รหัสบัตรจอดรถ
     * @param string $base_url URL หลัก (default: http://localhost)
     * @return string Base64 encoded QR Code image
     */
    public static function generateParkingQRCode($card_id, $base_url = 'http://localhost') {
        $url = $base_url . "/view.php?card_id=" . urlencode($card_id);
        return self::generateQRCode($url);
    }
}
