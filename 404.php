<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Halaman Tidak Ditemukan';

include __DIR__ . '/includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center">
        <div class="error mx-auto">404</div>
        <p class="lead text-gray-800 mb-5">Halaman Tidak Ditemukan</p>
        <p class="text-gray-500 mb-0">Sepertinya Anda tersesat...</p>
        <a href="<?php echo base_url(); ?>" class="btn btn-primary btn-lg mt-4">
            <i class="bi bi-house me-2"></i>Kembali ke Beranda
        </a>
        
        <div class="mt-5">
            <h6 class="text-muted">Mungkin Anda mencari:</h6>
            <div class="d-flex flex-column gap-2 mt-3">
                <a href="<?php echo base_url('events.php'); ?>" class="text-decoration-none">
                    <i class="bi bi-calendar-event me-2"></i>Daftar Event
                </a>
                <a href="<?php echo base_url('user/dashboard.php'); ?>" class="text-decoration-none">
                    <i class="bi bi-person me-2"></i>Dashboard User
                </a>
                <a href="<?php echo base_url('admin/'); ?>" class="text-decoration-none">
                    <i class="bi bi-gear me-2"></i>Panel Admin
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.error {
    font-size: 7rem;
    position: relative;
    line-height: 1;
    width: 12.5rem;
    color: #4e73df;
}

.error:before {
    content: attr(data-text);
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    color: #f8f9fc;
    overflow: hidden;
    clip: rect(0, 12.5rem, 0, 0);
    animation: glitch 1s linear infinite reverse;
}

@keyframes glitch {
    0% {
        clip: rect(42px, 9999px, 44px, 0);
    }
    5% {
        clip: rect(12px, 9999px, 59px, 0);
    }
    10% {
        clip: rect(48px, 9999px, 29px, 0);
    }
    15% {
        clip: rect(42px, 9999px, 73px, 0);
    }
    20% {
        clip: rect(63px, 9999px, 27px, 0);
    }
    25% {
        clip: rect(34px, 9999px, 55px, 0);
    }
    30% {
        clip: rect(86px, 9999px, 73px, 0);
    }
    35% {
        clip: rect(20px, 9999px, 20px, 0);
    }
    40% {
        clip: rect(26px, 9999px, 60px, 0);
    }
    45% {
        clip: rect(25px, 9999px, 66px, 0);
    }
    50% {
        clip: rect(57px, 9999px, 98px, 0);
    }
    55% {
        clip: rect(5px, 9999px, 46px, 0);
    }
    60% {
        clip: rect(82px, 9999px, 31px, 0);
    }
    65% {
        clip: rect(54px, 9999px, 27px, 0);
    }
    70% {
        clip: rect(28px, 9999px, 99px, 0);
    }
    75% {
        clip: rect(45px, 9999px, 69px, 0);
    }
    80% {
        clip: rect(23px, 9999px, 85px, 0);
    }
    85% {
        clip: rect(54px, 9999px, 84px, 0);
    }
    90% {
        clip: rect(45px, 9999px, 47px, 0);
    }
    95% {
        clip: rect(37px, 9999px, 20px, 0);
    }
    100% {
        clip: rect(4px, 9999px, 91px, 0);
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
