<?php
/**
 * Parking Service
 * เซอร์วิสสำหรับจัดการข้อมูลการจอดรถ
 */

require_once __DIR__ . '/../config/DatabaseConnection.php';

class ParkingService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabase();
    }
    
    /**
     * ดึงข้อมูลบัตรจอดรถที่ยังไม่ได้สแกน QR Code
     * @return array|null ข้อมูลบัตรจอดรถ หรือ null ถ้าไม่มีข้อมูล
     */
    public function getUnscannedCard() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM parking_cards WHERE is_qrscan = 0 ORDER BY id ASC LIMIT 1");
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("ไม่สามารถดึงข้อมูลบัตรจอดรถได้: " . $e->getMessage());
        }
    }
    
    /**
     * ดึงข้อมูลบัตรจอดรถตาม card_id
     * @param string $card_id รหัสบัตรจอดรถ
     * @return array|null ข้อมูลบัตรจอดรถ หรือ null ถ้าไม่มีข้อมูล
     */
    public function getCardById($card_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("ไม่สามารถดึงข้อมูลบัตรจอดรถได้: " . $e->getMessage());
        }
    }
    
    /**
     * อัปเดตสถานะการสแกน QR Code
     * @param string $card_id รหัสบัตรจอดรถ
     * @return bool true หากสำเร็จ
     */
    public function updateQRScanStatus($card_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE parking_cards SET is_qrscan = 1, is_ready = 1 WHERE card_id = ?");
            return $stmt->execute([$card_id]);
        } catch (PDOException $e) {
            throw new Exception("ไม่สามารถอัปเดตสถานะการสแกนได้: " . $e->getMessage());
        }
    }

    /**
     * อัปเดตสถานะช่องจอดรถ
     * @param string $card_id รหัสบัตรจอดรถ
     * @return bool true หากสำเร็จ
     * @throws Exception หากไม่สามารถอัปเดตสถานะได้
     */
    public function updateSlotStatus($card_id) {
        try {
            // ดึงข้อมูลบัตรจอดรถ
            $stmt = $this->pdo->prepare("SELECT * FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            $card = $stmt->fetch();

            // อัพเดตสถานะช่องจอดรถ
            if ($card) {
                $stmt = $this->pdo->prepare("UPDATE parking_slots SET is_occupied = 1 WHERE slot_number = ?");
                return $stmt->execute([$card['slot_number']]);
            } else {
                throw new Exception("ไม่พบบัตรจอดรถที่มีรหัส: " . $card_id);
            }
        } catch (PDOException $e) {
            throw new Exception("ไม่สามารถอัปเดตสถานะช่องจอดรถได้: " . $e->getMessage());
        }
    }

    /**
     * ลบข้อมูลบัตรจอดรถ
     * @param string $card_id รหัสบัตรจอดรถ
     * @return bool true หากสำเร็จ
     */
    public function deleteCard($card_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM parking_cards WHERE card_id = ?");
            return $stmt->execute([$card_id]);
        } catch (PDOException $e) {
            throw new Exception("ไม่สามารถลบข้อมูลบัตรจอดรถได้: " . $e->getMessage());
        }
    }
    
    /**
     * คำนวณค่าจอดรถ
     * @param string $entry_time เวลาที่เข้ามาจอดรถ
     * @param int $hourly_rate ค่าบริการต่อชั่วโมง (default: 20)
     * @param int $free_hours จำนวนชั่วโมงฟรี (default: 3)
     * @return array ข้อมูลการคำนวณค่าจอดรถ
     */
    public function calculateParkingFee($entry_time, $hourly_rate = 20, $free_hours = 3) {
        try {
            $entry_datetime = new DateTime($entry_time);
            $now = new DateTime();
            $now->setTimezone(new DateTimeZone('Asia/Bangkok'));

            $interval = $entry_datetime->diff($now);
            $total_hours = $interval->days * 24 + $interval->h + ($interval->i > 0 ? 1 : 0);
            $chargeable_hours = max(0, $total_hours - $free_hours);
            $total_price = $chargeable_hours * $hourly_rate;

            $free_until = clone $entry_datetime;
            $free_until->modify("+{$free_hours} hours");
            $free_until_timestamp = $free_until->getTimestamp() * 1000;

            return [
                'entry_time' => $entry_datetime,
                'current_time' => $now,
                'total_hours' => $total_hours,
                'chargeable_hours' => $chargeable_hours,
                'total_price' => $total_price,
                'free_until_timestamp' => $free_until_timestamp,
                'interval' => $interval
            ];
        } catch (Exception $e) {
            throw new Exception("ไม่สามารถคำนวณค่าจอดรถได้: " . $e->getMessage());
        }
    }
}
