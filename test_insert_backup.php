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
    
    $stmt = $pdo->query("SELECT COUNT(*) as scanned FROM parking_cards WHERE is_qrscan = 1");
    $stats['scanned_cards'] = $stmt->fetch()['scanned'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as unscanned FROM parking_cards WHERE is_qrscan = 0");
    $stats['unscanned_cards'] = $stmt->fetch()['unscanned'];
    
    // ตรวจสอบว่ามีตาราง parking_slots หรือไม่
    $stmt = $pdo->query("SHOW TABLES LIKE 'parking_slots'");
    $has_slots_table = $stmt->rowCount() > 0;
    
    if ($has_slots_table) {
        $stmt = $pdo->query("SELECT COUNT(*) as occupied FROM parking_slots WHERE is_occupied = 1");
        $stats['occupied_slots'] = $stmt->fetch()['occupied'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as available FROM parking_slots WHERE is_occupied = 0");
        $stats['available_slots'] = $stmt->fetch()['available'];
    } else {
        $stats['occupied_slots'] = 0;
        $stats['available_slots'] = 0;
        $stats['slots_table_missing'] = true;
    }
} catch (Exception $e) {
    $stats['error'] = $e->getMessage();
}

// จัดการ Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_test_data'])) {
            // เพิ่มข้อมูลทดสอบ
            if (!$has_slots_table || $stats['available_slots'] <= 0) {
                // สร้างช่องจอดรถแบบจำลอง
                $slot_number = rand(1, 20);
            } else {
                $stmt = $pdo->query("SELECT slot_number FROM parking_slots WHERE is_occupied = 0 ORDER BY RAND() LIMIT 1");
                $slot = $stmt->fetch();
                $slot_number = $slot ? $slot['slot_number'] : rand(1, 20);
            }

            $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24); 
            $license_plate = "ทดสอบ" . rand(1000, 9999); 
            $expire_at = date("Y-m-d H:i:s", strtotime("+3 days")); 
            $entry_time = date("Y-m-d H:i:s");

            $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);

            if ($has_slots_table) {
                $pdo->prepare("UPDATE parking_slots SET is_occupied = 1 WHERE slot_number = ?")->execute([$slot_number]);
            }

            $result_message = "<div class='alert alert-success'>✅ เพิ่มข้อมูลทดสอบสำเร็จ<br>Slot: $slot_number<br>Card ID: $card_id<br>ทะเบียน: $license_plate</div>";
            
        } elseif (isset($_POST['reset_qr_scan'])) {
            // รีเซ็ต QR Scan
            $stmt = $pdo->prepare("UPDATE parking_cards SET is_qrscan = 0, is_ready = 0");
            $stmt->execute();
            $result_message = "<div class='alert alert-info'>🔄 รีเซ็ต QR Scan สำเร็จ</div>";
            
        } elseif (isset($_POST['clear_all'])) {
            // ลบข้อมูลทั้งหมด
            $pdo->query("DELETE FROM parking_cards");
            if ($has_slots_table) {
                $pdo->query("UPDATE parking_slots SET is_occupied = 0");
            }
            $result_message = "<div class='alert alert-warning'>🗑️ ลบข้อมูลทั้งหมดสำเร็จ</div>";
            
        } elseif (isset($_POST['create_slots_table'])) {
            // สร้างตาราง parking_slots
            $pdo->query("CREATE TABLE IF NOT EXISTS parking_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slot_number INT UNIQUE NOT NULL,
                is_occupied BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // เพิ่มช่องจอดรถ 20 ช่อง
            for ($i = 1; $i <= 20; $i++) {
                $pdo->prepare("INSERT IGNORE INTO parking_slots (slot_number) VALUES (?)")->execute([$i]);
            }
            
            $result_message = "<div class='alert alert-success'>✅ สร้างตาราง parking_slots สำเร็จ (20 ช่อง)</div>";
            $has_slots_table = true;
            
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
        } elseif (isset($_POST['add_card']) && isset($_POST['license_plate']) && isset($_POST['slot_number'])) {
            // เพิ่มบัตรใหม่จากเครื่องมือแอดมิน
            $card_id = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 30)), 0, 24);
            $license_plate = $_POST['license_plate'];
            $slot_number = $_POST['slot_number'];
            $expire_at = date("Y-m-d H:i:s", strtotime("+3 days"));
            $entry_time = date("Y-m-d H:i:s");
            
            $stmt = $pdo->prepare("INSERT INTO parking_cards (card_id, license_plate, entry_time, slot_number, expire_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$card_id, $license_plate, $entry_time, $slot_number, $expire_at]);
            
            $pdo->prepare("UPDATE parking_slots SET is_occupied = 1 WHERE slot_number = ?")->execute([$slot_number]);
            
            $result_message = "<div class='alert alert-success'>✅ เพิ่มบัตรใหม่สำเร็จ<br>Card ID: $card_id<br>ทะเบียน: $license_plate<br>Slot: $slot_number</div>";
            
        } elseif (isset($_POST['reset_qr']) && isset($_POST['card_id'])) {
            // รีเซ็ต QR Code
            $card_id = $_POST['card_id'];
            $stmt = $pdo->prepare("UPDATE parking_cards SET is_qrscan = 0, is_ready = 0 WHERE card_id = ?");
            $stmt->execute([$card_id]);
            
            $result_message = "<div class='alert alert-info'>🔄 รีเซ็ต QR Code สำเร็จ<br>Card ID: $card_id</div>";
            
        } elseif (isset($_POST['delete_card']) && isset($_POST['card_id'])) {
            // ลบบัตรจากเครื่องมือแอดมิน
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
        }
        
        // รีเฟรชสถิติ
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $result_message = "<div class='alert alert-danger'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
    }
}

// ดึงข้อมูลบัตรล่าสุด
try {
    $stmt = $pdo->query("SELECT * FROM parking_cards ORDER BY id DESC LIMIT 10");
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
    <title>🚗 ระบบจัดการหลังบ้าน - Smart Parking</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .navbar .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .navbar .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .navbar .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .icon {
            font-size: 2rem;
            float: right;
            color: #667eea;
            opacity: 0.7;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .controls-panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            height: fit-content;
        }
        
        .controls-panel h2 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .control-group {
            margin-bottom: 1.5rem;
        }
        
        .control-group h4 {
            color: #333;
            margin-bottom: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #333;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 154, 158, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(168, 237, 234, 0.4);
        }
        
        .data-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .data-section h2 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .delete-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .delete-btn:hover {
            background: #ff5252;
            transform: scale(1.05);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #cce7ff 0%, #b8daff 100%);
            color: #004085;
            border-left: 4px solid #007bff;
        }
        
        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .floating-action:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .admin-section {
            margin-bottom: 2rem;
        }
        
        .admin-section h3 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
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
        
        .time-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .time-form input,
        .time-form select {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 14px;
        }
        
        .time-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .time-form input:focus,
        .time-form select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
        }
        
        .time-form button {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1><i class="fas fa-car"></i> ระบบจัดการหลังบ้าน</h1>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> หน้าหลัก</a>
                <a href="debug.php"><i class="fas fa-bug"></i> Debug</a>
                <a href="view.php"><i class="fas fa-eye"></i> ดูข้อมูล</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($result_message): ?>
            <?php echo $result_message; ?>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-id-card"></i></div>
                <h3>บัตรทั้งหมด</h3>
                <div class="number"><?php echo $stats['total_cards'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-qrcode"></i></div>
                <h3>สแกนแล้ว</h3>
                <div class="number"><?php echo $stats['scanned_cards'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3>รอสแกน</h3>
                <div class="number pulse"><?php echo $stats['unscanned_cards'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-parking"></i></div>
                <h3>ช่องว่าง</h3>
                <div class="number"><?php echo $stats['available_slots'] ?? 0; ?></div>
            </div>
        </div>

        <div class="main-content">
            <!-- Controls Panel -->
            <div class="controls-panel">
                <h2><i class="fas fa-cogs"></i> เครื่องมือจัดการ</h2>
                
                <div class="control-group">
                    <h4><i class="fas fa-plus-circle"></i> เพิ่มข้อมูล</h4>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="add_test_data" class="btn btn-primary">
                            <i class="fas fa-plus"></i> เพิ่มบัตรทดสอบ
                        </button>
                    </form>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="simulate_time_scenarios" class="btn btn-primary">
                            <i class="fas fa-theater-masks"></i> สร้างสถานการณ์ทดสอบ
                        </button>
                    </form>
                </div>

                <div class="control-group">
                    <h4><i class="fas fa-clock"></i> จัดการเวลา</h4>
                    <div class="time-controls">
                        <form method="POST" style="margin-bottom: 1rem;">
                            <input type="hidden" id="time_card_id" name="card_id" value="">
                            <div style="display: grid; gap: 0.5rem; margin-bottom: 0.8rem;">
                                <label style="font-size: 0.9rem; color: #666;">Card ID:</label>
                                <select id="time_card_select" style="padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: monospace;">
                                    <option value="">เลือกบัตรจอดรถ...</option>
                                    <?php foreach ($recent_cards as $card): ?>
                                        <option value="<?php echo $card['card_id']; ?>">
                                            <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <label style="font-size: 0.9rem; color: #666;">เวลาเข้าใหม่:</label>
                                <input type="datetime-local" name="new_entry_time" 
                                       style="padding: 8px; border: 1px solid #ddd; border-radius: 6px;" 
                                       value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                            <button type="submit" name="update_entry_time" class="btn btn-warning">
                                <i class="fas fa-edit"></i> แก้ไขเวลาเข้า
                            </button>
                        </form>
                        
                        <form method="POST" style="margin-bottom: 1rem;">
                            <input type="hidden" id="hours_card_id" name="card_id" value="">
                            <div style="display: grid; gap: 0.5rem; margin-bottom: 0.8rem;">
                                <label style="font-size: 0.9rem; color: #666;">Card ID:</label>
                                <select id="hours_card_select" style="padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: monospace;">
                                    <option value="">เลือกบัตรจอดรถ...</option>
                                    <?php foreach ($recent_cards as $card): ?>
                                        <option value="<?php echo $card['card_id']; ?>">
                                            <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <label style="font-size: 0.9rem; color: #666;">เพิ่ม/ลด ชั่วโมง:</label>
                                <select name="hours_to_add" style="padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
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
                            </div>
                            <button type="submit" name="add_hours_to_entry" class="btn btn-warning">
                                <i class="fas fa-clock"></i> ปรับชั่วโมง
                            </button>
                        </form>
                    </div>
                </div>

                <div class="control-group">
                    <h4><i class="fas fa-sync-alt"></i> รีเซ็ตระบบ</h4>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="reset_qr_scan" class="btn btn-warning" 
                                onclick="return confirm('รีเซ็ต QR Scan ทั้งหมด?')">
                            <i class="fas fa-undo"></i> รีเซ็ต QR Scan
                        </button>
                    </form>
                </div>

                <?php if (isset($stats['slots_table_missing']) && $stats['slots_table_missing']): ?>
                <div class="control-group">
                    <h4><i class="fas fa-database"></i> สร้างตาราง</h4>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="create_slots_table" class="btn btn-success">
                            <i class="fas fa-table"></i> สร้างตาราง Slots
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="control-group">
                    <h4><i class="fas fa-trash"></i> ลบข้อมูล</h4>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="clear_all" class="btn btn-danger" 
                                onclick="return confirm('ลบข้อมูลทั้งหมด? การดำเนินการนี้ไม่สามารถย้อนกลับได้!')">
                            <i class="fas fa-trash-alt"></i> ลบข้อมูลทั้งหมด
                        </button>
                    </form>
                </div>

                <div class="control-group">
                    <h4><i class="fas fa-info-circle"></i> ข้อมูลระบบ</h4>
                    <div style="font-size: 0.9rem; color: #666; line-height: 1.6;">
                        <p><strong>เวลาปัจจุบัน:</strong><br><?php echo date('Y-m-d H:i:s'); ?></p>
                        <p><strong>เซิร์ฟเวอร์:</strong><br><?php echo $_SERVER['SERVER_NAME']; ?></p>
                        <p><strong>PHP Version:</strong><br><?php echo PHP_VERSION; ?></p>
                    </div>
                </div>
            </div>

            <!-- Data Display -->
            <div class="data-section">
                <h2><i class="fas fa-list"></i> บัตรจอดรถล่าสุด</h2>
                
                <?php if (empty($recent_cards)): ?>
                    <div style="text-align: center; color: #666; padding: 2rem;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>ยังไม่มีข้อมูลบัตรจอดรถ</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> ID</th>
                                    <th>Card ID</th>
                                    <th>ทะเบียนรถ</th>
                                    <th>ช่องจอด</th>
                                    <th>เวลาเข้า</th>
                                    <th>เวลาออก</th>
                                    <th>ระยะเวลาจอด</th>
                                    <th>ค่าจอดรถ</th>
                                    <th>สถานะ</th>
                                    <th><i class="fas fa-cog"></i> จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_cards as $card): ?>
                                <tr>
                                    <td><?php echo $card['id']; ?></td>
                                    <td style="font-family: monospace; font-size: 0.85rem;">
                                        <?php echo substr($card['card_id'], 0, 12) . '...'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($card['license_plate']); ?></td>
                                    <td><?php echo $card['slot_number']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($card['entry_time'])); ?></td>
                                    <td><?php echo $card['exit_time'] ? date('d/m/Y H:i', strtotime($card['exit_time'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        if ($card['entry_time'] && $card['exit_time']) {
                                            $entry = new DateTime($card['entry_time']);
                                            $exit = new DateTime($card['exit_time']);
                                            $interval = $entry->diff($exit);
                                            echo $interval->format('%h ชม. %i นาที');
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($card['entry_time'] && $card['exit_time']) {
                                            $entry = new DateTime($card['entry_time']);
                                            $exit = new DateTime($card['exit_time']);
                                            $interval = $entry->diff($exit);
                                            $hours = $interval->h + ($interval->i / 60);
                                            $rate = 20; // บาทต่อชั่วโมง
                                            $fee = $hours * $rate;
                                            echo number_format($fee, 2) . ' บาท';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($card['is_qrscan']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> สแกนแล้ว
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> รอสแกน
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="card_id" value="<?php echo $card['card_id']; ?>">
                                            <button type="submit" name="delete_card" class="delete-btn"
                                                    onclick="return confirm('ลบบัตรนี้?')">
                                                <i class="fas fa-trash"></i>
                                            </form>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Tools Section -->
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
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action" onclick="location.reload()" title="รีเฟรชหน้า">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        // Auto refresh every 30 seconds
        setInterval(() => {
            const unscannedElement = document.querySelector('.pulse .number');
            if (unscannedElement && parseInt(unscannedElement.textContent) > 0) {
                // Only refresh if there are unscanned cards
                console.log('Auto refreshing...');
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        // Update only the stats section
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(html, 'text/html');
                        const newStats = newDoc.querySelector('.dashboard-grid');
                        if (newStats) {
                            document.querySelector('.dashboard-grid').innerHTML = newStats.innerHTML;
                        }
                    })
                    .catch(error => console.log('Auto refresh failed:', error));
            }
        }, 30000);

        // Add click animations
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
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
