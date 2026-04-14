<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Test - QR Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-camera"></i> Camera QR Scanner Test
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <!-- Camera Selection -->
                            <div class="mb-2">
                                <label for="cameraSelect" class="form-label">Pilih Kamera:</label>
                                <select class="form-select" id="cameraSelect">
                                    <option value="">Memuat daftar kamera...</option>
                                </select>
                            </div>
                            <div id="qr-reader" style="width: 100%;"></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" id="startCamera" class="btn btn-success">
                                <i class="bi bi-camera-video"></i> Start Camera
                            </button>
                            <button type="button" id="stopCamera" class="btn btn-danger" style="display: none;">
                                <i class="bi bi-camera-video-off"></i> Stop Camera
                            </button>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Scanned Result:</label>
                            <div class="form-control" id="result" style="min-height: 50px; background-color: #f8f9fa;">
                                <span class="text-muted">No scan yet...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let html5QrCode = null;
        let isScanning = false;

        document.getElementById('startCamera').addEventListener('click', startCameraScanner);
        document.getElementById('stopCamera').addEventListener('click', stopCameraScanner);

        function startCameraScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };
            
            // Get available cameras
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    // Populate camera select dropdown
                    const cameraSelect = document.getElementById('cameraSelect');
                    cameraSelect.innerHTML = '';
                    
                    // Find back camera first for mobile
                    let backCameraId = null;
                    let frontCameraId = null;
                    
                    devices.forEach((device, index) => {
                        const label = device.label || `Camera ${index + 1}`;
                        const option = document.createElement('option');
                        option.value = device.id;
                        option.textContent = label;
                        cameraSelect.appendChild(option);
                        
                        // Identify camera types
                        if (label.toLowerCase().includes('back') || label.toLowerCase().includes('rear') || label.toLowerCase().includes('environment')) {
                            backCameraId = device.id;
                        } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                            frontCameraId = device.id;
                        }
                    });
                    
                    // Select back camera by default on mobile, or first camera if no back camera found
                    const selectedCameraId = backCameraId || devices[0].id;
                    cameraSelect.value = selectedCameraId;
                    
                    // Add change event listener for camera switching
                    cameraSelect.addEventListener('change', function() {
                        if (isScanning) {
                            stopCameraScanner();
                            startCameraScanner();
                        }
                    });
                    
                    // Start with selected camera
                    html5QrCode.start(
                        { deviceId: { exact: selectedCameraId } },
                        config,
                        (decodedText, decodedResult) => {
                            document.getElementById('result').innerHTML = 
                                `<strong>${decodedText}</strong><br><small class="text-success">Scan successful!</small>`;
                            
                            // Optional: Stop after successful scan
                            // stopCameraScanner();
                        },
                        (errorMessage) => {
                            // Handle scan error silently
                        }
                    ).catch((err) => {
                        // Fallback to environment facing mode (back camera)
                        html5QrCode.start(
                            { facingMode: "environment" },
                            config,
                            (decodedText, decodedResult) => {
                                document.getElementById('result').innerHTML = 
                                    `<strong>${decodedText}</strong><br><small class="text-success">Scan successful!</small>`;
                            },
                            (errorMessage) => {
                                // Handle scan error silently
                            }
                        ).catch((fallbackErr) => {
                            console.error(`Failed to start with environment mode: ${fallbackErr}`);
                            alert('Unable to access camera. Please ensure camera permissions are granted.');
                        });
                    });
                    
                    isScanning = true;
                    document.getElementById('startCamera').style.display = 'none';
                    document.getElementById('stopCamera').style.display = 'block';
                }
            }).catch(err => {
                console.error(`Error getting cameras: ${err}`);
                alert('Unable to find camera. Please ensure camera is connected and allowed.');
            });
        }

        function stopCameraScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    html5QrCode.clear();
                    document.getElementById('startCamera').style.display = 'block';
                    document.getElementById('stopCamera').style.display = 'none';
                    // Reset camera select
                    const cameraSelect = document.getElementById('cameraSelect');
                    cameraSelect.innerHTML = '<option value="">Memuat daftar kamera...</option>';
                }).catch((err) => {
                    console.error(`Failed to stop scanning: ${err}`);
                });
            }
        }
    </script>
</body>
</html>
