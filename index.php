<?php
$pdo = new PDO("mysql:host=localhost;dbname=parking", "root", "");

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
        $url = "http://localhost/parking/view.php?card_id=$card_id";

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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจอดรถอัจฉริยะ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/index.css">
</head>
<body class="min-h-screen">
    <div class="particles" id="particles"></div>
    
    <div class="container mx-auto px-4 py-8 h-screen flex flex-col">
        <header class="mb-6">
            <div class="flex justify-center items-center">
                <svg class="w-12 h-12 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
                <h1 class="text-4xl font-bold text-white text-center">ระบบจอดรถอัจฉริยะ</h1>
            </div>
            <p class="text-blue-100 text-center mt-2 text-xl">สแกน QR Code เพื่อเข้าสู่ระบบ</p>
        </header>
        
        <div class="flex-grow flex items-center justify-center">
            <div id="content" class="qr-container bg-white rounded-xl p-8 max-w-md w-full text-center">
                <div class="loading-animation mx-auto mb-4">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                <h2 class="text-2xl font-medium text-gray-700">กำลังโหลด QR Code...</h2>
                <p class="text-gray-500 mt-2">โปรดรอสักครู่</p>
            </div>
        </div>
        
        <footer class="mt-8 text-center text-blue-100 pb-6">
            <p>© 2023 ระบบจอดรถอัจฉริยะ | เวลาปัจจุบัน: <span id="current-time"></span></p>
            <div class="car-animation hidden md:block">
                <svg class="w-20 h-20 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>
        </footer>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('current-time').textContent = now.toLocaleDateString('th-TH', options);
        }
        
        setInterval(updateTime, 1000);
        updateTime();
        
        // Create particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 5 + 2;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.animation = `float ${duration}s infinite ease-in-out ${delay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Fetch QR Code
        function fetchQRCode() {
            fetch('index.php?fetch=1')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('content');
                    if (data.status === 'no_data') {
                        container.innerHTML = `
                            <div class="py-8">
                                <svg class="w-20 h-20 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <h2 class="text-3xl font-bold text-gray-800">${data.message}</h2>
                                <p class="text-gray-600 mt-3">ระบบพร้อมให้บริการ</p>
                            </div>
                        `;
                    } else if (data.status === 'success') {
                        container.innerHTML = `
                            <div class="py-4">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4">สแกนเพื่อเข้าสู่ระบบ</h2>
                                <div class="border-4 border-blue-600 inline-block p-3 rounded-lg bg-white">
                                    <img class="qr-image w-64 h-64" src="data:image/png;base64,${data.qr_code}" alt="QR Code">
                                </div>
                                <p class="text-gray-600 mt-4">กรุณาสแกน QR Code ด้วยโทรศัพท์มือถือของท่าน</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching QR Code:', error);
                    const container = document.getElementById('content');
                    container.innerHTML = `
                        <div class="py-8">
                            <svg class="w-20 h-20 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h2 class="text-2xl font-bold text-gray-800">เกิดข้อผิดพลาด</h2>
                            <p class="text-gray-600 mt-3">ไม่สามารถเชื่อมต่อกับระบบได้ กรุณาลองใหม่อีกครั้ง</p>
                        </div>
                    `;
                });
        }

        // Add floating animation
        document.styleSheets[0].insertRule(`
            @keyframes float {
                0%, 100% {
                    transform: translateY(0) rotate(0deg);
                }
                50% {
                    transform: translateY(-20px) rotate(10deg);
                }
            }
        `, document.styleSheets[0].cssRules.length);

        setInterval(fetchQRCode, 3000); // Fetch every 3 seconds
        window.onload = fetchQRCode;
    </script>
</body>
</html>