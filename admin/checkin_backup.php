<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Check-in Tiket';

// Process check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_code'])) {
    $kode_tiket = strtoupper(sanitize($_POST['kode_tiket'] ?? ''));
    
    if (empty($kode_tiket)) {
        set_flash_message('error', 'Kode tiket harus diisi');
    } else {
        $ticket_data = checkin_ticket($kode_tiket);
        
        if ($ticket_data) {
            set_flash_message('success', 
                'Check-in berhasil! ' . 
                '<br>Nama: ' . htmlspecialchars($ticket_data['nama_event']) . 
                '<br>Tanggal: ' . format_date($ticket_data['tanggal']) . 
                '<br>Waktu: ' . date('H:i:s')
            );
        } else {
            set_flash_message('error', 'Kode tiket tidak valid atau sudah check-in');
        }
    }
}

// Get recent check-ins
$query = "SELECT a.*, e.nama_event, e.tanggal, u.nama as nama_user 
          FROM attendee a 
          JOIN order_detail od ON a.id_detail = od.id_detail 
          JOIN orders o ON od.id_order = o.id_order 
          JOIN users u ON o.id_user = u.id_user 
          JOIN tiket t ON od.id_tiket = t.id_tiket 
          JOIN event e ON t.id_event = e.id_event 
          WHERE a.status_checkin = 'sudah' 
          ORDER BY a.waktu_checkin DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's events for quick access
$query = "SELECT DISTINCT e.id_event, e.nama_event, COUNT(a.id_attendee) as total_checkins,
                 COUNT(CASE WHEN a.status_checkin = 'sudah' THEN 1 END) as checked_in
          FROM event e 
          LEFT JOIN tiket t ON e.id_event = t.id_event 
          LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket 
          LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
          LEFT JOIN attendee a ON od.id_detail = a.id_detail 
          WHERE e.tanggal = CURDATE()
          GROUP BY e.id_event
          ORDER BY e.nama_event";
$stmt = $db->prepare($query);
$stmt->execute();
$today_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<!-- HTML5-QRCode Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Check-in Tiket</h1>
            <p class="page-subtitle">Scan atau input kode tiket untuk check-in</p>
        </div>
    </div>

    <!-- Check-in Form -->
    <div class="row mb-4">
        <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-qr-code-scan"></i> Scan / Input Kode Tiket
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Scan Mode Toggle -->
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="scanMode" id="manualMode" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary" for="manualMode">
                                    <i class="bi bi-keyboard"></i> Manual
                                </label>
                                
                                <input type="radio" class="btn-check" name="scanMode" id="cameraMode" autocomplete="off">
                                <label class="btn btn-outline-success" for="cameraMode">
                                    <i class="bi bi-camera"></i> Kamera
                                </label>
                            </div>
                            
                            <!-- Manual Input Form -->
                            <div id="manualInput">
                                <form method="POST" id="checkinForm">
                                    <div class="mb-3">
                                        <label for="kode_tiket" class="form-label">Kode Tiket</label>
                                        <div class="input-group input-group-lg">
                                            <input type="text" class="form-control" id="kode_tiket" name="kode_tiket" 
                                                   placeholder="Masukkan kode tiket" 
                                                   style="text-transform: uppercase;" 
                                                   autofocus required>
                                            <button type="submit" name="checkin_code" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Check-in
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle"></i> 
                                            Ketik kode tiket manual atau gunakan scanner kamera
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Camera Scanner -->
                            <div id="cameraScanner" style="display: none;">
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
                                    <button type="button" id="stopCamera" class="btn btn-danger">
                                        <i class="bi bi-camera-video-off"></i> Stop Kamera
                                    </button>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle"></i> 
                                    Arahkan kamera ke QR code atau barcode tiket
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Events -->
                <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-check"></i> Event Hari Ini
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($today_events)): ?>
                                <p class="text-muted">Tidak ada event hari ini</p>
                            <?php else: ?>
                                <?php foreach ($today_events as $event): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <div>
                                            <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Check-in: <?php echo $event['checked_in']; ?> / <?php echo $event['total_checkins']; ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php $percentage = $event['total_checkins'] > 0 ? ($event['checked_in'] / $event['total_checkins']) * 100 : 0; ?>
                                            <span class="badge bg-<?php echo $percentage >= 80 ? 'success' : ($percentage >= 50 ? 'warning' : 'secondary'); ?>">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Check-ins -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Check-in Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_checkins)): ?>
                        <p class="text-muted">Belum ada check-in hari ini</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Kode Tiket</th>
                                        <th>Event</th>
                                        <th>Pengunjung</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_checkins as $checkin): ?>
                                        <tr>
                                            <td><?php echo format_date($checkin['waktu_checkin'], 'H:i:s'); ?></td>
                                            <td>
                                                <code class="ticket-code"><?php echo htmlspecialchars($checkin['kode_tiket']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($checkin['nama_event']); ?></td>
                                            <td><?php echo htmlspecialchars($checkin['nama_user']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Sudah
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Confirmation Modal Component -->
<?php include __DIR__ . '/../includes/components/confirmation_modal.php'; ?>

<script>
let html5QrCode = null;
let isScanning = false;

// Auto-focus on kode_tiket input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('kode_tiket').focus();
    
    // Clear input after successful check-in
    <?php if (has_flash_message('success')): ?>
        document.getElementById('kode_tiket').value = '';
        document.getElementById('kode_tiket').focus();
    <?php endif; ?>
    
    // Handle scan mode toggle
    const manualMode = document.getElementById('manualMode');
    const cameraMode = document.getElementById('cameraMode');
    const manualInput = document.getElementById('manualInput');
    const cameraScanner = document.getElementById('cameraScanner');
    
    manualMode.addEventListener('change', function() {
        if (this.checked) {
            manualInput.style.display = 'block';
            cameraScanner.style.display = 'none';
            // Force clear any remaining video elements
            const qrReader = document.getElementById('qr-reader');
            qrReader.innerHTML = '';
            stopCameraScanner();
            document.getElementById('kode_tiket').focus();
        }
    });
    
    cameraMode.addEventListener('change', function() {
        if (this.checked) {
            manualInput.style.display = 'none';
            cameraScanner.style.display = 'block';
            startCameraScanner();
        }
    });
    
    // Stop camera button
    document.getElementById('stopCamera').addEventListener('click', stopCameraScanner);
    
    // Test modal button (temporary for debugging)
    const testBtn = document.createElement('button');
    testBtn.className = 'btn btn-outline-primary btn-sm mt-2';
    testBtn.textContent = 'Test Modal';
    testBtn.onclick = function() {
        console.log('Test button clicked');
        showConfirmationModal('TEST123');
    };
    document.getElementById('stopCamera').parentNode.appendChild(testBtn);
    
    // Modal confirmation button
    document.getElementById('confirmCheckinModalConfirm').addEventListener('click', function() {
        const modalElement = document.getElementById('confirmCheckinModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        const kodeTiket = modalElement.dataset.kodeTiket;
        
        console.log('Confirm check-in clicked for ticket:', kodeTiket);
        
        // Fill the form and submit
        document.getElementById('kode_tiket').value = kodeTiket;
        
        // Switch to manual mode
        document.getElementById('manualMode').checked = true;
        document.getElementById('manualMode').dispatchEvent(new Event('change'));
        
        // Hide modal
        modal.hide();
        
        // Submit form
        document.getElementById('checkinForm').submit();
    });
    
    // Modal cancel button
    document.getElementById('confirmCheckinModalCancel').addEventListener('click', function() {
        const modalElement = document.getElementById('confirmCheckinModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        
        console.log('Cancel check-in clicked');
        
        // Hide modal
        modal.hide();
        
        // Resume camera scanning
        if (html5QrCode && isScanning) {
            html5QrCode.resume();
            console.log('Camera resumed');
        }
        
        // Show camera scanner again
        document.getElementById('cameraScanner').style.display = 'block';
    });
    
    // Handle modal hidden event (when closed with X or backdrop)
    document.getElementById('confirmCheckinModal').addEventListener('hidden.bs.modal', function() {
        // Resume camera scanning if still in camera mode
        if (document.getElementById('cameraMode').checked && html5QrCode && isScanning) {
            html5QrCode.resume();
            console.log('Camera resumed on modal close');
            document.getElementById('cameraScanner').style.display = 'block';
        }
    });
});

// Handle Enter key for quick check-in
document.getElementById('checkinForm').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        console.log('Enter key pressed, submitting form');
        e.preventDefault();
        this.submit();
    }
});

// Log form submission
document.getElementById('checkinForm').addEventListener('submit', function(e) {
    console.log('Form submit event triggered');
    // Don't prevent default, let it submit normally
});

// Camera Scanner Functions
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
                const label = device.label || `Kamera ${index + 1}`;
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
                    // On successful scan
                    handleScanSuccess(decodedText);
                },
                (errorMessage) => {
                    // Handle scan error silently
                    // console.warn(errorMessage);
                }
            ).catch((err) => {
                console.error(`Unable to start scanning: ${err}`);
                // Fallback to environment facing mode (back camera)
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText, decodedResult) => {
                        handleScanSuccess(decodedText);
                    },
                    (errorMessage) => {
                        // Handle scan error silently
                    }
                ).catch((fallbackErr) => {
                    console.error(`Failed to start with environment mode: ${fallbackErr}`);
                    alert('Tidak dapat mengakses kamera. Pastikan Anda telah memberikan izin kamera.');
                    document.getElementById('manualMode').checked = true;
                    document.getElementById('manualMode').dispatchEvent(new Event('change'));
                });
            });
            
            isScanning = true;
        } else {
            alert('Tidak ada kamera yang ditemukan pada perangkat ini.');
            document.getElementById('manualMode').checked = true;
            document.getElementById('manualMode').dispatchEvent(new Event('change'));
        }
    }).catch(err => {
        console.error(`Error getting cameras: ${err}`);
        alert('Tidak dapat menemukan kamera. Pastikan kamera terhubung dan diizinkan.');
        document.getElementById('manualMode').checked = true;
        document.getElementById('manualMode').dispatchEvent(new Event('change'));
    });
}

function stopCameraScanner() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            html5QrCode.clear();
            // Reset camera select
            const cameraSelect = document.getElementById('cameraSelect');
            cameraSelect.innerHTML = '<option value="">Memuat daftar kamera...</option>';
        }).catch((err) => {
            console.error(`Failed to stop scanning: ${err}`);
        });
    }
}

function handleScanSuccess(decodedText) {
    console.log('QR Scan Success: ' + decodedText);
    
    // Hide camera scanner immediately to prevent black overlay
    document.getElementById('cameraScanner').style.display = 'none';
    console.log('Camera scanner hidden');
    
    // Pause scanning but don't stop completely
    if (html5QrCode && isScanning) {
        html5QrCode.pause();
        console.log('Camera paused');
    }
    
    // Show modal and fetch ticket info
    showConfirmationModal(decodedText.toUpperCase());
}

function showConfirmationModal(kodeTiket) {
    console.log('showConfirmationModal called with:', kodeTiket);
    
    const modalElement = document.getElementById('confirmCheckinModal');
    const modal = new bootstrap.Modal(modalElement);
    const modalLoading = document.getElementById('confirmCheckinModalLoading');
    const modalContent = document.getElementById('confirmCheckinModalContent');
    const modalAlert = document.getElementById('confirmCheckinModalAlert');
    
    // Reset modal state
    modalLoading.style.display = 'block';
    modalContent.style.display = 'none';
    modalAlert.style.display = 'none';
    modalAlert.className = 'alert';
    
    // Store the ticket code for later use (on the DOM element, not the modal instance)
    modalElement.dataset.kodeTiket = kodeTiket;
    console.log('Ticket code stored in modal dataset');
    
    // Show modal
    modal.show();
    console.log('Modal show() called');
    
    // Fetch ticket information
    const apiUrl = `../api/ticket_info.php?code=${encodeURIComponent(kodeTiket)}`;
    console.log('Fetching from:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('Fetch response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Fetch response data:', data);
            modalLoading.style.display = 'none';
            
            if (data.success) {
                const ticket = data.data;
                
                // Fill modal with ticket information
                document.getElementById('confirmCheckinModalKodeTiket').textContent = ticket.kode_tiket;
                document.getElementById('confirmCheckinModalEvent').textContent = ticket.nama_event;
                document.getElementById('confirmCheckinModalTanggal').textContent = ticket.tanggal;
                document.getElementById('confirmCheckinModalLokasi').textContent = ticket.lokasi;
                document.getElementById('confirmCheckinModalPengunjung').textContent = ticket.nama_pengunjung;
                document.getElementById('confirmCheckinModalEmail').textContent = ticket.email_pengunjung;
                document.getElementById('confirmCheckinModalTipeTiket').textContent = ticket.nama_tiket;
                document.getElementById('confirmCheckinModalHarga').textContent = ticket.harga;
                
                // Check if already checked in
                if (ticket.already_checked) {
                    modalAlert.className = 'alert alert-warning d-flex align-items-center';
                    modalAlert.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Perhatian!</strong> Tiket ini sudah check-in pada pukul ${ticket.waktu_checkin || '-'}
                        </div>
                    `;
                    modalAlert.style.display = 'block';
                    
                    // Disable confirm button
                    document.getElementById('confirmCheckinModalConfirm').disabled = true;
                    document.getElementById('confirmCheckinModalConfirm').innerHTML = 
                        '<i class="bi bi-check-circle"></i> Sudah Check-in';
                } else {
                    modalAlert.style.display = 'none';
                    
                    // Enable confirm button
                    document.getElementById('confirmCheckinModalConfirm').disabled = false;
                    document.getElementById('confirmCheckinModalConfirm').innerHTML = 
                        '<i class="bi bi-check-circle"></i> Ya, Check-in';
                }
                
                modalContent.style.display = 'block';
            } else {
                // Show error message
                modalAlert.className = 'alert alert-danger d-flex align-items-center';
                modalAlert.innerHTML = `
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <div>
                        <strong>Error!</strong> ${data.message}
                    </div>
                `;
                modalAlert.style.display = 'block';
                modalContent.style.display = 'none';
                
                // Disable confirm button
                document.getElementById('confirmCheckinModalConfirm').disabled = true;
            }
        })
        .catch(error => {
            console.error('Error fetching ticket info:', error);
            modalLoading.style.display = 'none';
            
            modalAlert.className = 'alert alert-danger d-flex align-items-center';
            modalAlert.innerHTML = `
                <i class="bi bi-x-circle-fill me-2"></i>
                <div>
                    <strong>Error!</strong> Gagal memuat informasi tiket. Silakan coba lagi.
                </div>
            `;
            modalAlert.style.display = 'block';
            modalContent.style.display = 'none';
            
            // Disable confirm button
            document.getElementById('confirmCheckinModalConfirm').disabled = true;
        });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
