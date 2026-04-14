// Camera Scanner JavaScript
let html5QrCode = null;
let isScanning = false;

// Initialize camera scanner when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Mode toggle event listeners
    const manualMode = document.getElementById('manualMode');
    const cameraMode = document.getElementById('cameraMode');
    const manualInput = document.getElementById('manualInput');
    const cameraScanner = document.getElementById('cameraScanner');
    
    if (manualMode && cameraMode && manualInput && cameraScanner) {
        manualMode.addEventListener('change', function() {
            if (this.checked) {
                manualInput.style.display = 'block';
                cameraScanner.style.display = 'none';
                document.getElementById('kode_tiket').focus();
                stopCameraScanner();
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
        const stopCameraBtn = document.getElementById('stopCamera');
        if (stopCameraBtn) {
            stopCameraBtn.addEventListener('click', stopCameraScanner);
        }
    }
    
    // Modal event listeners
    initModalEventListeners();
});

// Initialize modal event listeners
function initModalEventListeners() {
    // Modal confirmation button
    const confirmBtn = document.getElementById('confirmCheckinModalConfirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
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
    }
    
    // Modal cancel button
    const cancelBtn = document.getElementById('confirmCheckinModalCancel');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
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
    }
    
    // Handle modal hidden event
    const modal = document.getElementById('confirmCheckinModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            // Resume camera scanning if still in camera mode
            if (document.getElementById('cameraMode').checked && html5QrCode && isScanning) {
                html5QrCode.resume();
                console.log('Camera resumed on modal close');
                document.getElementById('cameraScanner').style.display = 'block';
            }
        });
    }
}

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
        const cameraSelect = document.getElementById('cameraSelect');
        
        if (devices && devices.length) {
            cameraSelect.innerHTML = '';
            
            // Default to back camera on mobile
            let selectedCameraId = devices[0].id;
            
            devices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.id;
                
                // Try to identify camera type
                if (device.label.toLowerCase().includes('back') || 
                    device.label.toLowerCase().includes('environment') ||
                    (index === 0 && devices.length > 1)) {
                    option.text = 'Kamera Belakang';
                    if (selectedCameraId === devices[0].id) {
                        selectedCameraId = device.id;
                    }
                } else if (device.label.toLowerCase().includes('front') || 
                          device.label.toLowerCase().includes('user')) {
                    option.text = 'Kamera Depan';
                } else {
                    option.text = device.label || `Kamera ${index + 1}`;
                }
                
                cameraSelect.appendChild(option);
            });
            
            // Add change event listener
            cameraSelect.addEventListener('change', function() {
                if (isScanning) {
                    html5QrCode.stop().then(() => {
                        isScanning = false;
                        startScanning(this.value);
                    }).catch((err) => {
                        console.error(`Failed to restart camera: ${err}`);
                    });
                }
            });
            
            // Start with selected camera
            startScanning(selectedCameraId);
        } else {
            cameraSelect.innerHTML = '<option value="">Tidak ada kamera ditemukan</option>';
        }
    }).catch(err => {
        console.error(`Error getting cameras: ${err}`);
        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.innerHTML = '<option value="">Gagal mengakses kamera</option>';
    });
}

function startScanning(cameraId) {
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrCode.start(
        { deviceId: { exact: cameraId } },
        config,
        handleScanSuccess,
        handleScanFailure
    ).then(() => {
        isScanning = true;
        console.log('Camera started successfully');
    }).catch((err) => {
        console.error(`Unable to start scanning: ${err}`);
        
        // Fallback to facingMode if deviceId fails
        if (err.toString().includes('deviceId')) {
            console.log('Trying fallback with facingMode...');
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                handleScanSuccess,
                handleScanFailure
            ).then(() => {
                isScanning = true;
                console.log('Camera started with fallback');
            }).catch((err2) => {
                console.error(`Fallback also failed: ${err2}`);
                alert('Gagal memulai kamera. Silakan periksa izin kamera dan coba lagi.');
            });
        }
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

function handleScanFailure(error) {
    // Don't log common scanning errors
    if (!error.includes('No QR code found') && 
        !error.includes('No MultiFormat Readers were able to detect the code')) {
        console.warn(`QR scan error: ${error}`);
    }
}

function showConfirmationModal(kodeTiket) {
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
    
    // Store the ticket code for later use
    modalElement.dataset.kodeTiket = kodeTiket;
    
    // Show modal
    modal.show();
    
    // Fetch ticket information
    const apiUrl = `../api/ticket_info.php?code=${encodeURIComponent(kodeTiket)}`;
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            modalLoading.style.display = 'none';
            
            if (data.success) {
                const ticket = data.data;
                
                // Fill modal with ticket information
                document.getElementById('confirmCheckinModalKodeTiket').textContent = ticket.kode_tiket;
                document.getElementById('confirmCheckinModalEvent').textContent = ticket.nama_event;
                document.getElementById('confirmCheckinModalTanggal').textContent = ticket.tanggal;
                document.getElementById('confirmCheckinModalPengunjung').textContent = ticket.nama_pengunjung;
                document.getElementById('confirmCheckinModalEmail').textContent = ticket.email_pengunjung;
                document.getElementById('confirmCheckinModalTipeTiket').textContent = ticket.nama_tiket;
                document.getElementById('confirmCheckinModalHarga').textContent = ticket.harga;
                document.getElementById('confirmCheckinModalQty').textContent = ticket.qty || 1;
                document.getElementById('confirmCheckinModalSubtotal').textContent = ticket.subtotal || ticket.harga;
                
                // Check if already checked in
                if (ticket.already_checked) {
                    // Add warning row to table
                    const warningRow = `
                        <tr>
                            <td colspan="2" class="text-center py-3">
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>Perhatian!</strong> Tiket ini sudah check-in pada pukul ${ticket.waktu_checkin || '-'}
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Insert warning row at the top of table
                    const table = document.querySelector('#confirmCheckinModalContent table');
                    if (table) {
                        table.insertAdjacentHTML('afterbegin', warningRow);
                    }
                    
                    // Disable confirm button
                    document.getElementById('confirmCheckinModalConfirm').disabled = true;
                } else {
                    // Enable confirm button
                    document.getElementById('confirmCheckinModalConfirm').disabled = false;
                }
                
                modalContent.style.display = 'block';
            } else {
                // Show error message in table
                modalContent.style.display = 'block';
                
                const errorContent = `
                    <tr>
                        <td colspan="2" class="text-center py-4">
                            <div class="text-danger">
                                <i class="bi bi-x-circle-fill display-4"></i>
                                <h5 class="mt-3">Error!</h5>
                                <p class="mb-0">${data.message}</p>
                                <small>Silakan coba lagi scan QR code</small>
                            </div>
                        </td>
                    </tr>
                `;
                
                // Clear and show error in table
                const table = document.querySelector('#confirmCheckinModalContent table');
                if (table) {
                    table.innerHTML = errorContent;
                }
                
                // Disable confirm button
                document.getElementById('confirmCheckinModalConfirm').disabled = true;
            }
        })
        .catch(error => {
            modalLoading.style.display = 'none';
            modalContent.style.display = 'block';
            
            // Show error in the table itself
            const tableContent = `
                <tr>
                    <td colspan="2" class="text-center py-4">
                        <div class="text-danger">
                            <i class="bi bi-x-circle-fill display-4"></i>
                            <h5 class="mt-3">Error!</h5>
                            <p class="mb-0">${error.message}</p>
                            <small>Silakan coba lagi scan QR code</small>
                        </div>
                    </td>
                </tr>
            `;
            
            // Clear and show error in table
            const table = document.querySelector('#confirmCheckinModalContent table');
            if (table) {
                table.innerHTML = tableContent;
            }
            
            // Disable confirm button
            document.getElementById('confirmCheckinModalConfirm').disabled = true;
        });
}

// Handle Enter key for quick check-in
document.addEventListener('DOMContentLoaded', function() {
    const checkinForm = document.getElementById('checkinForm');
    if (checkinForm) {
        checkinForm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('Enter key pressed, submitting form');
                e.preventDefault();
                this.submit();
            }
        });
        
        // Log form submission
        checkinForm.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
        });
    }
});
