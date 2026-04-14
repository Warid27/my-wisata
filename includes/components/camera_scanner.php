<?php
/**
 * Camera Scanner Component
 * 
 * Renders a camera scanner interface with QR code scanning functionality
 * 
 * @param string $reader_id ID for the QR reader element (default: 'qr-reader')
 * @param string $scanner_id ID for the scanner container (default: 'cameraScanner')
 * @param string $select_id ID for camera select dropdown (default: 'cameraSelect')
 * @param string $stop_id ID for stop button (default: 'stopCamera')
 */

// Default parameters
$reader_id = $reader_id ?? 'qr-reader';
$scanner_id = $scanner_id ?? 'cameraScanner';
$select_id = $select_id ?? 'cameraSelect';
$stop_id = $stop_id ?? 'stopCamera';
?>

<!-- Camera Scanner -->
<div id="<?php echo $scanner_id; ?>" style="display: none;">
    <div class="mb-3">
        <!-- Camera Selection -->
        <div class="mb-2">
            <label for="<?php echo $select_id; ?>" class="form-label">Pilih Kamera:</label>
            <select class="form-select" id="<?php echo $select_id; ?>">
                <option value="">Memuat daftar kamera...</option>
            </select>
        </div>
        <div id="<?php echo $reader_id; ?>" style="width: 100%;"></div>
    </div>
    <div class="d-grid gap-2">
        <button type="button" id="<?php echo $stop_id; ?>" class="btn btn-danger">
            <i class="bi bi-camera-video-off"></i> Stop Kamera
        </button>
    </div>
    <div class="form-text mt-2">
        <i class="bi bi-info-circle"></i> 
        Arahkan kamera ke QR code atau barcode tiket
    </div>
</div>
