// Toast Notification System
function showNotification(message, type = 'info') {
    const toastElement = document.getElementById('notificationToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastTitle = document.getElementById('toastTitle');
    const toastIcon = document.getElementById('toastIcon');
    
    // Set message
    toastMessage.textContent = message;
    
    // Configure based on type
    switch(type) {
        case 'success':
            toastTitle.textContent = 'Sukses';
            toastIcon.className = 'bi bi-check-circle-fill me-2 text-success';
            toastElement.classList.remove('bg-danger', 'bg-info', 'bg-warning');
            toastElement.classList.add('bg-success', 'text-white');
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const toast = bootstrap.Toast.getInstance(toastElement) || new bootstrap.Toast(toastElement);
                toast.hide();
            }, 3000);
            break;
            
        case 'error':
            toastTitle.textContent = 'Error';
            toastIcon.className = 'bi bi-exclamation-triangle-fill me-2 text-danger';
            toastElement.classList.remove('bg-success', 'bg-info', 'bg-warning');
            toastElement.classList.add('bg-danger', 'text-white');
            // Don't auto-dismiss errors
            break;
            
        case 'warning':
            toastTitle.textContent = 'Peringatan';
            toastIcon.className = 'bi bi-exclamation-triangle-fill me-2 text-warning';
            toastElement.classList.remove('bg-success', 'bg-info', 'bg-danger');
            toastElement.classList.add('bg-warning');
            // Auto-dismiss after 4 seconds
            setTimeout(() => {
                const toast = bootstrap.Toast.getInstance(toastElement) || new bootstrap.Toast(toastElement);
                toast.hide();
            }, 4000);
            break;
            
        default: // info
            toastTitle.textContent = 'Informasi';
            toastIcon.className = 'bi bi-info-circle-fill me-2 text-info';
            toastElement.classList.remove('bg-success', 'bg-danger', 'bg-warning');
            toastElement.classList.add('bg-info', 'text-white');
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                const toast = bootstrap.Toast.getInstance(toastElement) || new bootstrap.Toast(toastElement);
                toast.hide();
            }, 3000);
    }
    
    // Show the toast
    const toast = bootstrap.Toast.getInstance(toastElement) || new bootstrap.Toast(toastElement);
    toast.show();
}

// Confirmation Modal System
function showConfirmation(message, onConfirm, options = {}) {
    const modalElement = document.getElementById('confirmationModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmIcon = document.getElementById('confirmIcon');
    const confirmOk = document.getElementById('confirmOk');
    const confirmCancel = document.getElementById('confirmCancel');
    
    // Set message
    confirmMessage.textContent = message;
    
    // Set title and icon based on options
    if (options.title) {
        confirmTitle.textContent = options.title;
    } else {
        confirmTitle.textContent = 'Konfirmasi';
    }
    
    if (options.isDanger) {
        confirmIcon.className = 'bi bi-exclamation-triangle-fill me-2 text-danger';
        confirmOk.className = 'btn btn-danger';
        confirmOk.textContent = 'Hapus';
    } else {
        confirmIcon.className = 'bi bi-question-circle me-2 text-primary';
        confirmOk.className = 'btn btn-primary';
        confirmOk.textContent = options.okText || 'Ya';
    }
    
    if (options.cancelText) {
        confirmCancel.textContent = options.cancelText;
    } else {
        confirmCancel.textContent = 'Batal';
    }
    
    // Remove existing event listeners
    const newConfirmOk = confirmOk.cloneNode(true);
    confirmOk.parentNode.replaceChild(newConfirmOk, confirmOk);
    
    // Add click event listener
    newConfirmOk.addEventListener('click', function() {
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    // Show the modal
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    modal.show();
}

// Initialize toast when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const toastElement = document.getElementById('notificationToast');
    if (toastElement) {
        new bootstrap.Toast(toastElement);
    }
});