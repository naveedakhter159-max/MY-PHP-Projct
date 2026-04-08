/* PayRoll Pro - Main JS */

// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Initialize DataTables
function initDataTable(selector, options = {}) {
    const defaults = {
        responsive: true,
        pageLength: 10,
        language: {
            search: '',
            searchPlaceholder: 'Search...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            emptyTable: 'No data available',
        },
        dom: "<'row mb-3'<'col-sm-6 d-flex align-items-center'l><'col-sm-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>",
    };
    return $(selector).DataTable({ ...defaults, ...options });
}

// Confirm Delete
function confirmDelete(url, name) {
    Swal.fire({
        title: 'Delete ' + (name || 'this record') + '?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
        });
    }

    // Auto dismiss flash messages
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });

    // Sidebar active link on mobile close
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                toggleSidebar();
            }
        });
    });
});

// Flash message helper
function showToast(type, message) {
    const iconMap = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: iconMap[type] || 'info',
        title: message,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
    });
}

// Print payslip
function printPayslip() {
    window.print();
}
