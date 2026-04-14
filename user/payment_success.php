<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'Silakan login terlebih dahulu');
    redirect('user/login.php');
}

$page_title = 'Pembayaran Berhasil';

// Get invoice ID from URL parameter
$invoice_id = $_GET['invoice_id'] ?? '';

if ($invoice_id) {
    // Try to get order by Xendit invoice ID
    $query = "SELECT * FROM orders WHERE xendit_invoice_id = ? AND id_user = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$invoice_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        if ($order['status'] === 'paid') {
            set_flash_message('success', 'Pembayaran berhasil! Tiket Anda telah dibuat');
            redirect('user/my_tickets.php?order=' . $order['id_order']);
        } else {
            // Payment might still be processing, show processing page
            include __DIR__ . '/../includes/header.php';
            ?>
            
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card text-center">
                            <div class="card-body py-5">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h4>Memproses Pembayaran</h4>
                                <p class="text-muted">Pembayaran Anda sedang diproses. Mohon tunggu...</p>
                                
                                <script>
                                    // Check payment status every 3 seconds
                                    let attempts = 0;
                                    const maxAttempts = 20;
                                    
                                    function checkPaymentStatus() {
                                        fetch('<?php echo base_url("api/check_payment_status.php"); ?>?invoice_id=<?php echo $invoice_id; ?>')
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.status === 'paid') {
                                                    window.location.href = '<?php echo base_url("user/my_tickets.php?order=" . $order['id_order']); ?>';
                                                } else if (data.status === 'failed' || data.status === 'expired') {
                                                    window.location.href = '<?php echo base_url("user/payment.php?order=" . $order['id_order']); ?>';
                                                } else if (attempts < maxAttempts) {
                                                    attempts++;
                                                    setTimeout(checkPaymentStatus, 3000);
                                                } else {
                                                    // Max attempts reached, redirect to payment page
                                                    window.location.href = '<?php echo base_url("user/payment.php?order=" . $order['id_order']); ?>';
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                setTimeout(checkPaymentStatus, 3000);
                                            });
                                    }
                                    
                                    setTimeout(checkPaymentStatus, 3000);
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            include __DIR__ . '/../includes/footer.php';
            exit;
        }
    }
}

// Fallback to orders page if no specific invoice
redirect('user/history.php');
?>
