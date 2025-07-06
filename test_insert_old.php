<?php
/**
 * test_insert.php - ระบบจัดการหลังบ้าน (Admin Panel)
 */

require_once __DIR__ . '/config/DatabaseConnection.php';

$pdo = getDatabase();
$result_message = '';
$stats = [];

// ดึงสถิติระบบ
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM parking_cards");
    $stats['total_cards'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM parking_cards WHERE exit_time IS NULL");
    $stats['active_cards'] = $stmt->fetch()['active'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM parking_cards WHERE exit_time IS NOT NULL");
    $stats['completed_cards'] = $stmt->fetch()['completed'];
    
    // ตรวจสอบว่ามีตาราง parking_slots หรือไม่
    $stmt = $pdo->query("SHOW TABLES LIKE 'parking_slots'");
    $has_slots_table = $stmt->rowCount() > 0;
    
    if ($has_slots_table) {
        $stmt = $pdo->query("SELECT COUNT(*) as available FROM parking_slots WHERE is_occupied = 0");
        $stats['available_slots'] = $stmt->fetch()['available'];
    } else {
        $stats['available_slots'] = 20; // Default available slots
    }
} catch (Exception $e) {
    $stats['error'] = $e->getMessage();
}

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_card'])) {
            // เพิ่มบัตรทดสอบอัตโนมัติ
            $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24);
            $license_plate = "ทดสอบ" . rand(1000, 9999);
            $slot_number = rand(1, 20);
            $entry_time = date("Y-m-d H:i:s");
            $expire_at = date("Y-m-d H:i:s", strtotime("+3 days"));
            
            $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);
            
            $result_message = "<div class='alert alert-success'>🎉 เพิ่มบัตรทดสอบสำเร็จ<br>Card ID: $card_id<br>ทะเบียน: $license_plate<br>ช่องจอด: $slot_number</div>";
            
        } elseif (isset($_POST['add_custom_card'])) {
            // เพิ่มบัตรใหม่แบบกำหนดเอง
            $license_plate = $_POST['license_plate'];
            $slot_number = $_POST['slot_number'];
            $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24);
            $entry_time = date("Y-m-d H:i:s");
            $expire_at = date("Y-m-d H:i:s", strtotime("+3 days"));
            
            $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);
            
            $result_message = "<div class='alert alert-success'>🎉 เพิ่มบัตรสำเร็จ<br>Card ID: $card_id<br>ทะเบียน: $license_plate<br>ช่องจอด: $slot_number</div>";
            
        } elseif (isset($_POST['reset_qr'])) {
            // รีเซ็ต QR Code
            $card_id = $_POST['card_id'];
            $stmt = $pdo->prepare("UPDATE parking_cards SET is_qrscan = 0 WHERE card_id = ?");
            $stmt->execute([$card_id]);
            
            $result_message = "<div class='alert alert-warning'>🔄 รีเซ็ต QR Code สำเร็จ<br>Card ID: " . substr($card_id, 0, 12) . "...</div>";
            
        } elseif (isset($_POST['reset_qr'])) {
            // รีเซ็ต QR Code
            $card_id = $_POST['card_id'];
            $stmt = $pdo->prepare("UPDATE parking_cards SET is_qrscan = 0 WHERE card_id = ?");
            $stmt->execute([$card_id]);
            
            $result_message = "<div class='alert alert-warning'>🔄 รีเซ็ต QR Code สำเร็จ<br>Card ID: $card_id</div>";
            
        } elseif (isset($_POST['delete_card']) && isset($_POST['card_id'])) {
            // ลบบัตรเฉพาะ
            $card_id = $_POST['card_id'];
            $stmt = $pdo->prepare("SELECT slot_number FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            $card = $stmt->fetch();
            
            if ($card && $has_slots_table) {
                $pdo->prepare("UPDATE parking_slots SET is_occupied = 0 WHERE slot_number = ?")->execute([$card['slot_number']]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            
            $result_message = "<div class='alert alert-info'>🗑️ ลบบัตร $card_id สำเร็จ</div>";
            
        } elseif (isset($_POST['update_time']) && isset($_POST['card_id'])) {
            // แก้ไขเวลาเข้า-ออก
            $card_id = $_POST['card_id'];
            $entry_time = $_POST['entry_time'];
            $exit_time = $_POST['exit_time'] ?? null;
            
            $stmt = $pdo->prepare("UPDATE parking_cards SET entry_time = ?, exit_time = ? WHERE card_id = ?");
            $stmt->execute([$entry_time, $exit_time, $card_id]);
            
            $result_message = "<div class='alert alert-success'>⏰ แก้ไขเวลาสำเร็จ<br>Card ID: $card_id<br>เวลาเข้า: $entry_time" . ($exit_time ? "<br>เวลาออก: $exit_time" : "") . "</div>";
            
        } elseif (isset($_POST['add_hours']) && isset($_POST['card_id'])) {
            // เพิ่มชั่วโมงให้กับบัตร
            $card_id = $_POST['card_id'];
            $hours = (int)$_POST['hours'];
            
            $stmt = $pdo->prepare("SELECT entry_time, exit_time FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            $card = $stmt->fetch();
            
            if ($card) {
                $current_entry = $card['entry_time'];
                $new_entry = date('Y-m-d H:i:s', strtotime($current_entry . " -$hours hours"));
                
                $stmt = $pdo->prepare("UPDATE parking_cards SET entry_time = ? WHERE card_id = ?");
                $stmt->execute([$new_entry, $card_id]);
                
                $result_message = "<div class='alert alert-success'>⏰ เพิ่ม $hours ชั่วโมงสำเร็จ<br>Card ID: $card_id<br>เวลาเข้าใหม่: $new_entry</div>";
            }
            
        } elseif (isset($_POST['simulate_exit']) && isset($_POST['card_id'])) {
            // จำลองการออกจากลานจอดรถ
            $card_id = $_POST['card_id'];
            $exit_time = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE parking_cards SET exit_time = ? WHERE card_id = ?");
            $stmt->execute([$exit_time, $card_id]);
            
            $result_message = "<div class='alert alert-info'>🚗 จำลองการออกสำเร็จ<br>Card ID: $card_id<br>เวลาออก: $exit_time</div>";
            
        } elseif (isset($_POST['create_scenario']) && isset($_POST['scenario_type'])) {
            // สร้างสถานการณ์ทดสอบ
            $scenario = $_POST['scenario_type'];
            $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24);
            $license_plate = "ทดสอบ" . rand(1000, 9999);
            $slot_number = rand(1, 20);
            $expire_at = date("Y-m-d H:i:s", strtotime("+3 days"));
            
            switch ($scenario) {
                case 'short_term':
                    $entry_time = date('Y-m-d H:i:s', strtotime('-2 hours'));
                    $exit_time = null;
                    break;
                case 'overnight':
                    $entry_time = date('Y-m-d H:i:s', strtotime('-8 hours'));
                    $exit_time = null;
                    break;
                case 'full_day':
                    $entry_time = date('Y-m-d H:i:s', strtotime('-1 day'));
                    $exit_time = null;
                    break;
                case 'completed':
                    $entry_time = date('Y-m-d H:i:s', strtotime('-5 hours'));
                    $exit_time = date('Y-m-d H:i:s', strtotime('-1 hour'));
                    break;
                default:
                    $entry_time = date('Y-m-d H:i:s');
                    $exit_time = null;
            }
            
            $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, exit_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$card_id, $license_plate, $entry_time, $exit_time, $slot_number, $expire_at]);
            
            $result_message = "<div class='alert alert-success'>🎭 สร้างสถานการณ์ '$scenario' สำเร็จ<br>Card ID: $card_id<br>ทะเบียน: $license_plate<br>เวลาเข้า: $entry_time" . ($exit_time ? "<br>เวลาออก: $exit_time" : "") . "</div>";
            
        } elseif (isset($_POST['clear_all'])) {
            // ล้างข้อมูลทั้งหมด
            $pdo->query("DELETE FROM parking_cards");
            if ($has_slots_table) {
                $pdo->query("UPDATE parking_slots SET is_occupied = 0");
            }
            $result_message = "<div class='alert alert-warning'>🧹 ล้างข้อมูลทั้งหมดสำเร็จ</div>";
            
        } elseif (isset($_POST['create_tables'])) {
            // สร้างตาราง parking_slots
            $pdo->query("CREATE TABLE IF NOT EXISTS parking_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slot_number INT NOT NULL UNIQUE,
                is_occupied TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // เพิ่มช่องจอดรถ 20 ช่อง
            for ($i = 1; $i <= 20; $i++) {
                $pdo->prepare("INSERT IGNORE INTO parking_slots (slot_number) VALUES (?)")->execute([$i]);
            }
            
            $result_message = "<div class='alert alert-success'>🏗️ สร้างตาราง parking_slots สำเร็จ</div>";
            
        } elseif (isset($_POST['update_entry_time']) && isset($_POST['card_id'])) {
            // แก้ไขเวลาเข้า
            $card_id = $_POST['card_id'];
            $new_entry_time = $_POST['new_entry_time'];
            
            $stmt = $pdo->prepare("UPDATE parking_cards SET entry_time = ? WHERE card_id = ?");
            $stmt->execute([$new_entry_time, $card_id]);
            
            $result_message = "<div class='alert alert-success'>⏰ แก้ไขเวลาเข้าสำเร็จ<br>Card ID: " . substr($card_id, 0, 12) . "...<br>เวลาเข้าใหม่: $new_entry_time</div>";
            
        } elseif (isset($_POST['add_hours_to_entry']) && isset($_POST['card_id'])) {
            // เพิ่ม/ลด ชั่วโมง
            $card_id = $_POST['card_id'];
            $hours = (int)$_POST['hours_to_add'];
            
            $stmt = $pdo->prepare("SELECT entry_time FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            $card = $stmt->fetch();
            
            if ($card) {
                $current_entry = $card['entry_time'];
                $new_entry = date('Y-m-d H:i:s', strtotime($current_entry . " $hours hours"));
                
                $stmt = $pdo->prepare("UPDATE parking_cards SET entry_time = ? WHERE card_id = ?");
                $stmt->execute([$new_entry, $card_id]);
                
                $action = $hours > 0 ? "เพิ่ม" : "ลด";
                $result_message = "<div class='alert alert-success'>⏰ $action " . abs($hours) . " ชั่วโมงสำเร็จ<br>Card ID: " . substr($card_id, 0, 12) . "...<br>เวลาเข้าใหม่: $new_entry</div>";
            }
            
        } elseif (isset($_POST['simulate_exit_now']) && isset($_POST['card_id'])) {
            // จำลองการออกขณะนี้
            $card_id = $_POST['card_id'];
            $exit_time = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE parking_cards SET exit_time = ? WHERE card_id = ?");
            $stmt->execute([$exit_time, $card_id]);
            
            $result_message = "<div class='alert alert-info'>🚗 จำลองการออกสำเร็จ<br>Card ID: " . substr($card_id, 0, 12) . "...<br>เวลาออก: $exit_time</div>";
            
        } elseif (isset($_POST['simulate_time_scenarios'])) {
            // สร้างสถานการณ์ทดสอบหลายแบบ
            $scenarios = [
                ['type' => 'short_term', 'hours' => -2, 'name' => 'จอดสั้น'],
                ['type' => 'medium_term', 'hours' => -5, 'name' => 'จอดกลาง'],
                ['type' => 'overnight', 'hours' => -8, 'name' => 'ค้างคืน'],
                ['type' => 'full_day', 'hours' => -24, 'name' => 'จอดทั้งวัน']
            ];
            
            $created_count = 0;
            foreach ($scenarios as $scenario) {
                $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24);
                $license_plate = $scenario['name'] . rand(1000, 9999);
                $slot_number = rand(1, 20);
                $expire_at = date("Y-m-d H:i:s", strtotime("+3 days"));
                $entry_time = date('Y-m-d H:i:s', strtotime($scenario['hours'] . ' hours'));
                
                $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);
                $created_count++;
            }
            
            $result_message = "<div class='alert alert-success'>🎭 สร้างสถานการณ์ทดสอบ $created_count รายการสำเร็จ</div>";
        }
        
    } catch (Exception $e) {
        $result_message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
    }
}

// ดึงข้อมูลล่าสุด
$recent_cards = [];
try {
    $stmt = $pdo->query("SELECT * FROM parking_cards ORDER BY entry_time DESC LIMIT 10");
    $recent_cards = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_cards = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ระบบจอดรถอัจฉริยะ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .admin-section {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .admin-section h3 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(5px);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h4 {
            color: #fff;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .time-tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .tool-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .tool-card h4 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .admin-form,
        .time-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .admin-form input,
        .time-form input,
        .time-form select {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 14px;
        }

        .admin-form input::placeholder,
        .time-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .admin-form input:focus,
        .time-form input:focus,
        .time-form select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-success {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(45deg, #ff9800, #f57c00);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, #f44336, #d32f2f);
            color: white;
        }

        .btn-info {
            background: linear-gradient(45deg, #2196F3, #1976D2);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(45deg, #9C27B0, #7B1FA2);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            color: #fff;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid rgba(76, 175, 80, 0.5);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.3);
            border: 1px solid rgba(255, 152, 0, 0.5);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.3);
            border: 1px solid rgba(244, 67, 54, 0.5);
        }

        .alert-info {
            background: rgba(33, 150, 243, 0.3);
            border: 1px solid rgba(33, 150, 243, 0.5);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .data-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .status {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status.active {
            background: rgba(76, 175, 80, 0.3);
            color: #4CAF50;
        }

        .status.completed {
            background: rgba(33, 150, 243, 0.3);
            color: #2196F3;
        }

        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .refresh-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .stats-grid,
            .tools-grid,
            .time-tools-grid {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2em;
            }

            .data-table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Admin Panel</h1>
            <p>ระบบจัดการหลังบ้าน - ลานจอดรถอัจฉริยะ</p>
        </div>

        <?php if ($result_message): ?>
            <?php echo $result_message; ?>
        <?php endif; ?>

        <div class="admin-section">
            <h3>📊 สถิติระบบ</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>จำนวนบัตรทั้งหมด</h4>
                    <p class="stat-number"><?php echo $stats['total_cards']; ?></p>
                </div>
                <div class="stat-card">
                    <h4>กำลังจอดอยู่</h4>
                    <p class="stat-number"><?php echo $stats['active_cards']; ?></p>
                </div>
                <div class="stat-card">
                    <h4>ช่องจอดว่าง</h4>
                    <p class="stat-number"><?php echo $stats['available_slots']; ?></p>
                </div>
                <div class="stat-card">
                    <h4>ออกแล้ว</h4>
                    <p class="stat-number"><?php echo $stats['completed_cards']; ?></p>
                </div>
            </div>
        </div>

        <!-- Time Management Tools -->
        <div class="admin-section">
            <h3>⏰ เครื่องมือจัดการเวลาทดสอบ</h3>
            <div class="time-tools-grid">
                <div class="tool-card">
                    <h4>🕐 แก้ไขเวลาเข้า</h4>
                    <form method="POST" class="time-form">
                        <select name="card_id" required>
                            <option value="">เลือกบัตรจอดรถ...</option>
                            <?php foreach ($recent_cards as $card): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="datetime-local" name="new_entry_time" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                        <button type="submit" name="update_entry_time" class="btn btn-primary">แก้ไขเวลาเข้า</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>⏱️ เพิ่ม/ลด ชั่วโมง</h4>
                    <form method="POST" class="time-form">
                        <select name="card_id" required>
                            <option value="">เลือกบัตรจอดรถ...</option>
                            <?php foreach ($recent_cards as $card): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="hours_to_add" required>
                            <option value="">เลือกจำนวนชั่วโมง</option>
                            <option value="-24">ลด 24 ชม. (1 วันที่แล้ว)</option>
                            <option value="-12">ลด 12 ชม.</option>
                            <option value="-8">ลด 8 ชม.</option>
                            <option value="-5">ลด 5 ชม.</option>
                            <option value="-4">ลด 4 ชม. (เกินฟรี 1 ชม.)</option>
                            <option value="-3">ลด 3 ชม. (หมดฟรีพอดี)</option>
                            <option value="-2">ลด 2 ชม. (ยังฟรีอยู่)</option>
                            <option value="-1">ลด 1 ชม.</option>
                            <option value="1">เพิ่ม 1 ชม.</option>
                            <option value="2">เพิ่ม 2 ชม.</option>
                            <option value="3">เพิ่ม 3 ชม.</option>
                            <option value="5">เพิ่ม 5 ชม.</option>
                            <option value="8">เพิ่ม 8 ชม.</option>
                            <option value="12">เพิ่ม 12 ชม.</option>
                            <option value="24">เพิ่ม 24 ชม.</option>
                        </select>
                        <button type="submit" name="add_hours_to_entry" class="btn btn-success">ปรับชั่วโมง</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🚗 จำลองการออก</h4>
                    <form method="POST" class="time-form">
                        <select name="card_id" required>
                            <option value="">เลือกบัตรจอดรถ...</option>
                            <?php foreach ($recent_cards as $card): ?>
                                <?php if (!$card['exit_time']): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="simulate_exit_now" class="btn btn-info">จำลองออกขณะนี้</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🎭 สร้างสถานการณ์ทดสอบ</h4>
                    <form method="POST" class="time-form">
                        <p style="color: rgba(255,255,255,0.8); font-size: 0.9em; margin-bottom: 10px;">
                            สร้างข้อมูลทดสอบ 4 สถานการณ์:<br>
                            • จอดสั้น (2 ชม.)<br>
                            • จอดกลาง (5 ชม.)<br>
                            • ค้างคืน (8 ชม.)<br>
                            • จอดทั้งวัน (24 ชม.)
                        </p>
                        <button type="submit" name="simulate_time_scenarios" class="btn btn-warning">สร้างสถานการณ์ทดสอบ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h3>🛠️ เครื่องมือแอดมิน</h3>
            <div class="tools-grid">
                <div class="tool-card">
                    <h4>🆕 เพิ่มบัตรใหม่</h4>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="add_card" value="1">
                        <input type="text" name="license_plate" placeholder="ทะเบียนรถ" required>
                        <input type="number" name="slot_number" placeholder="หมายเลขช่อง" min="1" required>
                        <button type="submit" class="btn btn-success">เพิ่มบัตร</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🔄 รีเซ็ต QR Code</h4>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="reset_qr" value="1">
                        <input type="text" name="card_id" placeholder="Card ID" required>
                        <button type="submit" class="btn btn-warning">รีเซ็ต QR</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🗑️ ลบบัตร</h4>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="delete_card" value="1">
                        <input type="text" name="card_id" placeholder="Card ID" required>
                        <button type="submit" class="btn btn-danger">ลบบัตร</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🧹 ล้างข้อมูลทั้งหมด</h4>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="clear_all" value="1">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลทั้งหมด?')">ล้างข้อมูล</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🏗️ สร้างตาราง</h4>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="create_tables" value="1">
                        <button type="submit" class="btn btn-info">สร้างตาราง</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h3>📋 ข้อมูลล่าสุด</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Card ID</th>
                        <th>ทะเบียนรถ</th>
                        <th>ช่องจอด</th>
                        <th>เวลาเข้า</th>
                        <th>เวลาออก</th>
                        <th>ระยะเวลาจอด</th>
                        <th>ค่าจอดรถ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($recent_cards) {
                        foreach ($recent_cards as $card) {
                            $entry_time = new DateTime($card['entry_time']);
                            $exit_time = $card['exit_time'] ? new DateTime($card['exit_time']) : null;
                            
                            // คำนวณเวลาและค่าใช้จ่าย
                            $hours_parked = 0;
                            $parking_fee = 0;
                            $status = 'กำลังจอด';
                            
                            if ($exit_time) {
                                $interval = $entry_time->diff($exit_time);
                                $hours_parked = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);
                                $parking_fee = $hours_parked * 20; // 20 บาท/ชั่วโมง
                                $status = 'ออกแล้ว';
                            } else {
                                $now = new DateTime();
                                $interval = $entry_time->diff($now);
                                $hours_parked = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);
                                $parking_fee = $hours_parked * 20;
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($card['card_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($card['license_plate']) . "</td>";
                            echo "<td>" . $card['slot_number'] . "</td>";
                            echo "<td>" . $entry_time->format('Y-m-d H:i:s') . "</td>";
                            echo "<td>" . ($exit_time ? $exit_time->format('Y-m-d H:i:s') : '-') . "</td>";
                            echo "<td>" . $hours_parked . " ชั่วโมง</td>";
                            echo "<td>" . $parking_fee . " บาท</td>";
                            echo "<td><span class='status " . ($exit_time ? 'completed' : 'active') . "'>$status</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align: center; color: #ccc;'>ไม่มีข้อมูล</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <button class="refresh-btn" onclick="location.reload()">🔄</button>
    </div>

    <script>
        // Auto refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);

        // Add ripple effect to buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
