<?php
/**
 * ระบบจอดรถอัจฉริยะ - หน้าหลัก
 * S    <title>ระบบจอดรถอัจฉริยะ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/index.css"> Parking System - Main Page
 */

// โหลด Services
require_once __DIR__ . '/services/ParkingService.php';
require_once __DIR__ . '/services/QRCodeService.php';

// จัดการคำขอ API
if (isset($_GET['fetch'])) {
    header('Content-Type: application/json');
    
    try {
        $parkingService = new ParkingService();
        $card = $parkingService->getUnscannedCard();

        if (!$card) {
            echo json_encode([
                'status' => 'no_data', 
                'message' => 'ยินดีต้อนรับ'
            ]);
        } else {
            $card_id = $card['card_id'];
            $qr_code = QRCodeService::generateParkingQRCode($card_id);

            echo json_encode([
                'status' => 'success', 
                'qr_code' => $qr_code,
                'card_id' => $card_id
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
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
            <!-- <div class="mt-4">
                <button id="debugBtn" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                    🔧 Debug QR Code
                </button>
            </div> -->
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
            console.log('🔄 Starting fetchQRCode...');
            
            const url = window.location.pathname + '?fetch=1';
            console.log('📡 Making request to:', url);
            
            fetch(url)
                .then(response => {
                    console.log('📥 Response received:', response);
                    console.log('📊 Response status:', response.status);
                    console.log('📋 Response headers:', [...response.headers.entries()]);
                    
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    
                    return response.text(); // Get as text first to debug
                })
                .then(text => {
                    console.log('📝 Raw response:', text);
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('✅ Data parsed successfully:', data);
                        
                        const container = document.getElementById('content');
                        if (data.status === 'no_data') {
                            console.log('ℹ️ No data - showing welcome message');
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
                            console.log('🎉 Success - displaying QR code for card:', data.card_id);
                            container.innerHTML = `
                                <div class="py-4">
                                    <h2 class="text-2xl font-bold text-gray-800 mb-4">สแกนเพื่อเข้าสู่ระบบ</h2>
                                    <div class="border-4 border-blue-600 inline-block p-3 rounded-lg bg-white">
                                        <img class="qr-image w-64 h-64" src="data:image/png;base64,${data.qr_code}" alt="QR Code">
                                    </div>
                                    <p class="text-gray-600 mt-4">กรุณาสแกน QR Code ด้วยโทรศัพท์มือถือของท่าน</p>
                                    <p class="text-gray-500 mt-2 text-sm">Card ID: ${data.card_id}</p>
                                </div>
                            `;
                        } else if (data.status === 'error') {
                            console.error('❌ API Error:', data.message);
                            container.innerHTML = `
                                <div class="py-8">
                                    <svg class="w-20 h-20 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h2 class="text-2xl font-bold text-gray-800">เกิดข้อผิดพลาด</h2>
                                    <p class="text-gray-600 mt-3">${data.message}</p>
                                    <p class="text-gray-500 mt-2 text-sm">File: ${data.file || 'N/A'}</p>
                                    <p class="text-gray-500 text-sm">Line: ${data.line || 'N/A'}</p>
                                </div>
                            `;
                        }
                    } catch (parseError) {
                        console.error('🔥 JSON Parse Error:', parseError);
                        console.error('🔥 Raw response was:', text);
                        
                        const container = document.getElementById('content');
                        container.innerHTML = `
                            <div class="py-8">
                                <svg class="w-20 h-20 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h2 class="text-2xl font-bold text-gray-800">JSON Parse Error</h2>
                                <p class="text-gray-600 mt-3">Response was not valid JSON</p>
                                <details class="text-gray-500 mt-2 text-sm">
                                    <summary>Raw Response (click to expand)</summary>
                                    <pre class="bg-gray-100 p-2 rounded mt-2 text-xs">${text}</pre>
                                </details>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('🔥 Fetch Error:', error);
                    const container = document.getElementById('content');
                    container.innerHTML = `
                        <div class="py-8">
                            <svg class="w-20 h-20 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h2 class="text-2xl font-bold text-gray-800">เกิดข้อผิดพลาด</h2>
                            <p class="text-gray-600 mt-3">ไม่สามารถเชื่อมต่อกับระบบได้: ${error.message}</p>
                            <p class="text-gray-500 mt-2">กรุณาเปิด Developer Console เพื่อดูรายละเอียดข้อผิดพลาด</p>
                        </div>
                    `;
                });
        }

        // Add floating animation safely
        function addFloatingAnimation() {
            try {
                // Create a style element instead of accessing external CSS
                const style = document.createElement('style');
                style.textContent = `
                    .particle {
                        position: absolute;
                        background-color: rgba(255, 255, 255, 0.1);
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 1;
                    }
                    
                    @keyframes float {
                        0%, 100% {
                            transform: translateY(0) rotate(0deg);
                        }
                        50% {
                            transform: translateY(-20px) rotate(10deg);
                        }
                    }
                    
                    .particle {
                        animation: float 15s infinite ease-in-out;
                    }
                    
                    .loading-animation {
                        display: inline-block;
                        position: relative;
                        width: 80px;
                        height: 80px;
                    }
                    
                    .loading-animation div {
                        position: absolute;
                        top: 33px;
                        width: 13px;
                        height: 13px;
                        border-radius: 50%;
                        background: #3B82F6;
                        animation-timing-function: cubic-bezier(0, 1, 1, 0);
                    }
                    
                    .loading-animation div:nth-child(1) {
                        left: 8px;
                        animation: loading1 0.6s infinite;
                    }
                    
                    .loading-animation div:nth-child(2) {
                        left: 8px;
                        animation: loading2 0.6s infinite;
                    }
                    
                    .loading-animation div:nth-child(3) {
                        left: 32px;
                        animation: loading2 0.6s infinite;
                    }
                    
                    .loading-animation div:nth-child(4) {
                        left: 56px;
                        animation: loading3 0.6s infinite;
                    }
                    
                    @keyframes loading1 {
                        0% { transform: scale(0); }
                        100% { transform: scale(1); }
                    }
                    
                    @keyframes loading3 {
                        0% { transform: scale(1); }
                        100% { transform: scale(0); }
                    }
                    
                    @keyframes loading2 {
                        0% { transform: translate(0, 0); }
                        100% { transform: translate(24px, 0); }
                    }
                `;
                document.head.appendChild(style);
                console.log('✅ Animations added successfully');
            } catch (error) {
                console.log('⚠️ Could not add animations:', error);
            }
        }
        
        // Initialize animations
        addFloatingAnimation();

        setInterval(fetchQRCode, 3000); // Fetch every 3 seconds
        window.onload = fetchQRCode;
        
        // Debug button
        document.getElementById('debugBtn').addEventListener('click', function() {
            console.log('🔧 Debug button clicked');
            fetchQRCode();
        });
    </script>
</body>
</html>