<?php
/**
 * test_insert.php - ระบบจัดการหลังบ้าน (Admin Panel) - Complete Edition
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
    
    $stmt = $pdo->query("SELECT COUNT(*) as scanned FROM parking_cards WHERE is_qrscan = 1");
    $stats['scanned_cards'] = $stmt->fetch()['scanned'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as unscanned FROM parking_cards WHERE is_qrscan = 0");
    $stats['unscanned_cards'] = $stmt->fetch()['unscanned'];
    
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
        if (isset($_POST['add_test_card'])) {
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
            
        } elseif (isset($_POST['delete_card']) && isset($_POST['card_id'])) {
            // ลบบัตรเฉพาะ
            $card_id = $_POST['card_id'];
            $stmt = $pdo->prepare("DELETE FROM parking_cards WHERE card_id = ?");
            $stmt->execute([$card_id]);
            
            $result_message = "<div class='alert alert-info'>🗑️ ลบบัตรสำเร็จ<br>Card ID: " . substr($card_id, 0, 12) . "...</div>";
            
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
        
        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }
        
        .admin-section h3 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .time-tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .tool-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .tool-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        
        .tool-card h4 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .tool-card p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }
        
        .admin-form,
        .time-form {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .admin-form input,
        .admin-form select,
        .time-form input,
        .time-form select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 14px;
            font-family: 'Prompt', sans-serif;
        }
        
        .admin-form input:focus,
        .admin-form select:focus,
        .time-form input:focus,
        .time-form select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(244, 67, 54, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.4);
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
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
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
            z-index: 1000;
        }
        
        .floating-action:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }
        
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .tools-grid,
            .time-tools-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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
                <div class="icon"><i class="fas fa-car"></i></div>
                <h3>กำลังจอดอยู่</h3>
                <div class="number pulse"><?php echo $stats['active_cards'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3>ออกแล้ว</h3>
                <div class="number"><?php echo $stats['completed_cards'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-parking"></i></div>
                <h3>ช่องว่าง</h3>
                <div class="number"><?php echo $stats['available_slots'] ?? 0; ?></div>
            </div>
        </div>

        <!-- Time Management Tools -->
        <div class="admin-section">
            <h3>⏰ เครื่องมือจัดการเวลาทดสอบ</h3>
            <div class="time-tools-grid">
                <div class="tool-card">
                    <h4>🕐 แก้ไขเวลาเข้า</h4>
                    <p>แก้ไขเวลาเข้าของบัตรจอดรถเพื่อทดสอบการคำนวณค่าจอด</p>
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
                        <button type="submit" name="update_entry_time" class="btn btn-primary">
                            <i class="fas fa-edit"></i> แก้ไขเวลาเข้า
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>⏱️ เพิ่ม/ลด ชั่วโมง</h4>
                    <p>ปรับเวลาเข้าโดยการเพิ่มหรือลดชั่วโมงจอดรถ</p>
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
                        <button type="submit" name="add_hours_to_entry" class="btn btn-success">
                            <i class="fas fa-clock"></i> ปรับชั่วโมง
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🚗 จำลองการออก</h4>
                    <p>ตั้งเวลาออกเป็นเวลาปัจจุบันเพื่อทดสอบการคำนวณค่าจอด</p>
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
                        <button type="submit" name="simulate_exit_now" class="btn btn-info">
                            <i class="fas fa-sign-out-alt"></i> จำลองออกขณะนี้
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🎭 สร้างสถานการณ์ทดสอบ</h4>
                    <p>สร้างข้อมูลทดสอบ 4 สถานการณ์: จอดสั้น (2 ชม.), จอดกลาง (5 ชม.), ค้างคืน (8 ชม.), จอดทั้งวัน (24 ชม.)</p>
                    <form method="POST" class="time-form">
                        <button type="submit" name="simulate_time_scenarios" class="btn btn-warning">
                            <i class="fas fa-magic"></i> สร้างสถานการณ์ทดสอบ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Admin Tools -->
        <div class="admin-section">
            <h3>🛠️ เครื่องมือแอดมิน</h3>
            <div class="tools-grid">
                <div class="tool-card">
                    <h4>🆕 เพิ่มบัตรทดสอบอัตโนมัติ</h4>
                    <p>เพิ่มบัตรทดสอบแบบสุ่มทะเบียนและช่องจอด</p>
                    <form method="POST" class="admin-form">
                        <button type="submit" name="add_test_card" class="btn btn-success">
                            <i class="fas fa-plus"></i> เพิ่มบัตรทดสอบ
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🆕 เพิ่มบัตรใหม่ (กำหนดเอง)</h4>
                    <p>เพิ่มบัตรใหม่โดยกำหนดทะเบียนและช่องจอดเอง</p>
                    <form method="POST" class="admin-form">
                        <input type="text" name="license_plate" placeholder="ทะเบียนรถ" required>
                        <input type="number" name="slot_number" placeholder="หมายเลขช่อง" min="1" max="20" required>
                        <button type="submit" name="add_custom_card" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> เพิ่มบัตรใหม่
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🔄 รีเซ็ต QR Code</h4>
                    <p>รีเซ็ต QR Code ของบัตรเฉพาะให้กลับมาเป็นยังไม่สแกน</p>
                    <form method="POST" class="admin-form">
                        <select name="card_id" required>
                            <option value="">เลือกบัตรจอดรถ...</option>
                            <?php foreach ($recent_cards as $card): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="reset_qr" class="btn btn-warning">
                            <i class="fas fa-undo"></i> รีเซ็ต QR
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🗑️ ลบบัตรเฉพาะ</h4>
                    <p>ลบบัตรจอดรถที่เลือกออกจากระบบ</p>
                    <form method="POST" class="admin-form">
                        <select name="card_id" required>
                            <option value="">เลือกบัตรจอดรถ...</option>
                            <?php foreach ($recent_cards as $card): ?>
                                <option value="<?php echo $card['card_id']; ?>">
                                    <?php echo substr($card['card_id'], 0, 12); ?>... - <?php echo $card['license_plate']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="delete_card" class="btn btn-danger" onclick="return confirm('ลบบัตรนี้?')">
                            <i class="fas fa-trash"></i> ลบบัตร
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🧹 ล้างข้อมูลทั้งหมด</h4>
                    <p>⚠️ ลบข้อมูลบัตรจอดรถทั้งหมดออกจากระบบ</p>
                    <form method="POST" class="admin-form">
                        <button type="submit" name="clear_all" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูลทั้งหมด?')">
                            <i class="fas fa-trash-alt"></i> ล้างข้อมูล
                        </button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h4>🏗️ สร้างตารางระบบ</h4>
                    <p>สร้างตาราง parking_slots สำหรับระบบจัดการช่องจอดรถ (20 ช่อง)</p>
                    <form method="POST" class="admin-form">
                        <button type="submit" name="create_tables" class="btn btn-info">
                            <i class="fas fa-database"></i> สร้างตาราง
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Data Display -->
        <div class="admin-section">
            <h3><i class="fas fa-list"></i> ข้อมูลบัตรจอดรถล่าสุด</h3>
            
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
                            <?php foreach ($recent_cards as $card): ?>
                            <tr>
                                <td style="font-family: monospace;">
                                    <?php echo substr($card['card_id'], 0, 12) . '...'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($card['license_plate']); ?></td>
                                <td><?php echo $card['slot_number']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($card['entry_time'])); ?></td>
                                <td><?php echo $card['exit_time'] ? date('d/m/Y H:i', strtotime($card['exit_time'])) : '-'; ?></td>
                                <td>
                                    <?php
                                    if ($card['entry_time']) {
                                        $entry = new DateTime($card['entry_time']);
                                        $exit = $card['exit_time'] ? new DateTime($card['exit_time']) : new DateTime();
                                        $interval = $entry->diff($exit);
                                        $total_hours = $interval->h + ($interval->days * 24);
                                        $minutes = $interval->i;
                                        
                                        if ($total_hours > 0) {
                                            echo $total_hours . ' ชม. ' . $minutes . ' นาที';
                                        } else {
                                            echo $minutes . ' นาที';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($card['entry_time']) {
                                        $entry = new DateTime($card['entry_time']);
                                        $exit = $card['exit_time'] ? new DateTime($card['exit_time']) : new DateTime();
                                        $interval = $entry->diff($exit);
                                        $total_hours = $interval->h + ($interval->days * 24);
                                        $minutes = $interval->i;
                                        
                                        // คำนวณค่าจอดรถ: 3 ชั่วโมงแรกฟรี, หลังจากนั้น 20 บาท/ชั่วโมง
                                        $free_hours = 3;
                                        $rate_per_hour = 20;
                                        
                                        if ($total_hours <= $free_hours) {
                                            echo '<span style="color: green;">ฟรี</span>';
                                        } else {
                                            $chargeable_hours = $total_hours - $free_hours;
                                            if ($minutes > 0) $chargeable_hours += 1; // นับเศษนาทีเป็น 1 ชั่วโมง
                                            $fee = $chargeable_hours * $rate_per_hour;
                                            echo number_format($fee, 0) . ' บาท';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($card['exit_time']): ?>
                                        <span class="badge badge-info">ออกแล้ว</span>
                                    <?php elseif ($card['is_qrscan']): ?>
                                        <span class="badge badge-success">สแกนแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">รอสแกน</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-action" onclick="location.reload()" title="รีเฟรชหน้า">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        // Auto refresh every 30 seconds
        setInterval(() => {
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
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple animation
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
