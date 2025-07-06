# ระบบจอดรถอัจฉริยะ (Smart Parking System)

## คุณสมบัติหลัก
- สร้าง QR Code สำหรับการเข้าระบบ
- ระบบคำนวณค่าจอดรถอัตโนมัติ
- นับถอยหลังช่วงจอดฟรี 3 ชั่วโมง
- ค่าบริการ 20 บาท/ชั่วโมง
- การจัดการข้อมูลแบบ Real-time

## โครงสร้างโปรเจค

### ไฟล์หลัก
- `index.php` - หน้าหลักแสดง QR Code
- `view.php` - หน้าแสดงข้อมูลการจอดรถ
- `getData.php` - API ดึงข้อมูลบัตรจอดรถ
- `setData.php` - API อัปเดตสถานะบัตรจอดรถ
- `test_insert.php` - หน้าทดสอบเพิ่มข้อมูล

### โครงสร้างการ Refactor
```
config/
├── database.php           # การกำหนดค่าฐานข้อมูล
└── DatabaseConnection.php # คลาสการเชื่อมต่อฐานข้อมูล

services/
├── ParkingService.php     # บริการจัดการข้อมูลการจอดรถ
└── QRCodeService.php      # บริการสร้าง QR Code
```

## การติดตั้ง

### 1. ติดตั้ง Dependencies
```bash
composer install
```

### 2. ตั้งค่าฐานข้อมูล
แก้ไขไฟล์ `config/database.php`:
```php
$db_config = [
    'host' => 'localhost',
    'dbname' => 'parking',
    'username' => 'root',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```

### 3. นำเข้าฐานข้อมูล
```sql
-- นำเข้าไฟล์ parking.sql เข้าสู่ฐานข้อมูล
```

## การใช้งาน

### 1. หน้าหลัก (index.php)
- แสดง QR Code สำหรับสแกนเข้าใช้ระบบ
- ดึงข้อมูลบัตรจอดรถอัตโนมัติทุก 3 วินาที
- แสดงแอนิเมชันและเวลาปัจจุบัน

### 2. หน้าแสดงข้อมูล (view.php)
- แสดงข้อมูลการจอดรถพร้อมการคำนวณค่าใช้จ่าย
- นับถอยหลังช่วงจอดฟรี
- ปุ่มเปิดไม้กั้นเพื่อออกจากระบบ

### 3. API Endpoints
- `GET /getData.php` - ดึงข้อมูลบัตรจอดรถล่าสุด
- `GET /setData.php?slot_number=X` - อัปเดตสถานะบัตร

## คลาสและฟังก์ชันหลัก

### ParkingService
- `getUnscannedCard()` - ดึงบัตรที่ยังไม่ได้สแกน
- `getCardById($card_id)` - ดึงข้อมูลบัตรตาม ID
- `calculateParkingFee($entry_time)` - คำนวณค่าจอดรถ
- `updateQRScanStatus($card_id)` - อัปเดตสถานะสแกน
- `deleteCard($card_id)` - ลบข้อมูลบัตร

### QRCodeService
- `generateQRCode($data, $size)` - สร้าง QR Code ทั่วไป
- `generateParkingQRCode($card_id, $base_url)` - สร้าง QR Code สำหรับจอดรถ

### DatabaseConnection
- Singleton Pattern สำหรับการเชื่อมต่อฐานข้อมูล
- `getDatabase()` - Helper function ดึง PDO connection

## การแก้ไขปัญหา

### ปัญหาที่แก้ไขแล้ว:
1. ✅ การสร้าง QR Code ไม่สำเร็จ - แก้ไขการใช้งาน Endroid QR Code library
2. ✅ รวมการเชื่อมต่อฐานข้อมูลเป็น Singleton Pattern
3. ✅ สร้างไฟล์ config แยกเพื่อจัดการการตั้งค่า
4. ✅ Refactor code ให้เป็น Service Pattern
5. ✅ แก้ไขข้อผิดพลาดในการดึงข้อมูลเวลา

### การทดสอบ
```bash
# ทดสอบ syntax
php -l index.php
php -l view.php
php -l services/ParkingService.php
php -l services/QRCodeService.php

# ทดสอบการเชื่อมต่อฐานข้อมูล
php -r "require 'config/DatabaseConnection.php'; echo 'Database connection OK';"
```

## ข้อมูลเพิ่มเติม

- ระบบใช้ PHP 8.0+
- ใช้ PDO สำหรับการเชื่อมต่อฐานข้อมูล
- ใช้ Tailwind CSS สำหรับการตกแต่ง
- ใช้ JavaScript สำหรับการทำงานแบบ Real-time

## License
MIT License
