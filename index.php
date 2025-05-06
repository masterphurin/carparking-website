<?php
// $pdo = new PDO("mysql:host=localhost;dbname=parking", "root", "");

// require 'vendor/autoload.php';

// use Endroid\QrCode\Builder\Builder;
// use Endroid\QrCode\Encoding\Encoding;
// use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// $stmt = $pdo->query("SELECT * FROM parking_cards WHERE is_qrscan = 0 ORDER BY id ASC LIMIT 1");
// $card = $stmt->fetch();

// if (!$card) {
//     echo "❌ ไม่มีบัตรที่ต้องการสร้าง QR Code";
//     exit;
// }

// $card_id = $card['card_id'];
// $url = "http://localhost/parking/view.php?card_id=$card_id";

// $result = Builder::create()
//     ->data($url)
//     ->encoding(new Encoding('UTF-8'))
//     ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
//     ->build();

// header('Content-Type: image/png');
// echo $result->getString();
?>

<?php
$pdo = new PDO("mysql:host=192.168.1.138;dbname=parking", "pooh", "");

require 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

if (isset($_GET['fetch'])) {
    $stmt = $pdo->query("SELECT * FROM parking_cards WHERE is_qrscan = 0 ORDER BY id ASC LIMIT 1");
    $card = $stmt->fetch();

    if (!$card) {
        echo json_encode(['status' => 'no_data', 'message' => 'ยินดีต้อนรับ']);
    } else {
        $card_id = $card['card_id'];
        $url = "http://192.168.1.144/view.php?card_id=$card_id";

        $result = Builder::create()
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->build();

        echo json_encode(['status' => 'success', 'qr_code' => base64_encode($result->getString())]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking QR Code</title>
    <script>
        function fetchQRCode() {
            fetch('index.php?fetch=1')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('content');
                    if (data.status === 'no_data') {
                        container.innerHTML = `<h1>${data.message}</h1>`;
                    } else if (data.status === 'success') {
                        container.innerHTML = `<img src="data:image/png;base64,${data.qr_code}" alt="QR Code">`;
                    }
                })
                .catch(error => console.error('Error fetching QR Code:', error));
        }

        setInterval(fetchQRCode, 3000); // Fetch every 3 seconds
        window.onload = fetchQRCode;
    </script>
</head>
<body>
	<div id="content" style="display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center;">
		<h1>กำลังโหลด...</h1>
	</div>
	 
</body>
</html>