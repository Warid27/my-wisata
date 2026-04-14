<?php
/**
 * Confirmation Modal Component
 * 
 * Renders a Bootstrap modal for confirming check-in with ticket information display
 * 
 * @param string $id Modal ID (default: 'confirmCheckinModal')
 * @param string $title Modal title (default: 'Konfirmasi Check-in')
 * @param bool $static_backdrop Prevent closing with backdrop click (default: true)
 * @param bool $static_keyboard Prevent closing with ESC key (default: true)
 */

// Default parameters
$id = $id ?? 'confirmCheckinModal';
$title = $title ?? 'Konfirmasi Check-in';
$static_backdrop = $static_backdrop ?? true;
$static_keyboard = $static_keyboard ?? true;
$backdrop = $static_backdrop ? 'static' : 'true';
$keyboard = $static_keyboard ? 'false' : 'true';
?>

<!-- Confirmation Modal -->
<div class="modal fade" id="<?php echo $id; ?>" tabindex="-1" data-bs-backdrop="<?php echo $backdrop; ?>" data-bs-keyboard="<?php echo $keyboard; ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-qr-code-scan"></i> <?php echo htmlspecialchars($title); ?>
                </h5>
            </div>
            <div class="modal-body">
                <div id="<?php echo $id; ?>Loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat informasi tiket...</p>
                </div>
                <div id="<?php echo $id; ?>Content" style="display: none;">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <div>
                            <strong>Verifikasi tiket sebelum check-in</strong>
                        </div>
                    </div>
                    <table class="table table-borderless">
                        <tr>
                            <td width="30%"><strong>Kode Tiket:</strong></td>
                            <td id="<?php echo $id; ?>KodeTiket">-</td>
                        </tr>
                        <tr>
                            <td><strong>Event:</strong></td>
                            <td id="<?php echo $id; ?>Event">-</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal:</strong></td>
                            <td id="<?php echo $id; ?>Tanggal">-</td>
                        </tr>
                        <tr>
                            <td><strong>Pengunjung:</strong></td>
                            <td id="<?php echo $id; ?>Pengunjung">-</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td id="<?php echo $id; ?>Email">-</td>
                        </tr>
                        <tr>
                            <td><strong>Tipe Tiket:</strong></td>
                            <td id="<?php echo $id; ?>TipeTiket">-</td>
                        </tr>
                        <tr>
                            <td><strong>Harga:</strong></td>
                            <td id="<?php echo $id; ?>Harga">-</td>
                        </tr>
                        <tr>
                            <td><strong>Jumlah:</strong></td>
                            <td id="<?php echo $id; ?>Qty">-</td>
                        </tr>
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td id="<?php echo $id; ?>Subtotal">-</td>
                        </tr>
                    </table>
                    <div id="<?php echo $id; ?>Alert" class="alert" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="<?php echo $id; ?>Cancel">
                    <i class="bi bi-x-circle"></i> Batal
                </button>
                <button type="button" class="btn btn-success" id="<?php echo $id; ?>Confirm">
                    <i class="bi bi-check-circle"></i> Ya, Check-in
                </button>
            </div>
        </div>
    </div>
</div>
