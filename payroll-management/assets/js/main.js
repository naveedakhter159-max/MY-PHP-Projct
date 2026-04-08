/* Payroll System - Main JS */

// Confirm delete via SweetAlert2
function confirmDelete(url, name) {
    Swal.fire({
        title: 'Delete ' + (name || 'this record') + '?',
        text: 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53935',
        cancelButtonColor: '#9e9e9e',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
    }).then(r => { if (r.isConfirmed) window.location.href = url; });
}

// Show toast
function showToast(type, msg) {
    const icons = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
        toast: true, position: 'top-end',
        icon: icons[type] || 'info',
        title: msg,
        showConfirmButton: false,
        timer: 3500, timerProgressBar: true,
    });
}

// Sort table
function sortTable(tableId, col) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const th    = table.querySelectorAll('thead th')[col];
    const asc   = th.dataset.sort !== 'asc';
    table.querySelectorAll('thead th').forEach(t => { t.dataset.sort = ''; t.querySelector('.sort-icon') && (t.querySelector('.sort-icon').textContent = '⬍'); });
    th.dataset.sort = asc ? 'asc' : 'desc';
    if (th.querySelector('.sort-icon')) th.querySelector('.sort-icon').textContent = asc ? '↑' : '↓';
    rows.sort((a, b) => {
        const av = a.cells[col]?.textContent.trim() || '';
        const bv = b.cells[col]?.textContent.trim() || '';
        return asc ? av.localeCompare(bv, undefined, {numeric:true}) : bv.localeCompare(av, undefined, {numeric:true});
    });
    rows.forEach(r => tbody.appendChild(r));
}

// Client-side pagination
function setupPagination(tableId, perPage = 5) {
    const table  = document.getElementById(tableId);
    if (!table) return;
    const tbody  = table.querySelector('tbody');
    const footer = table.closest('.card')?.querySelector('.table-footer');
    if (!footer) return;
    let rows     = Array.from(tbody.querySelectorAll('tr'));
    let current  = 1;

    function render() {
        const total = Math.ceil(rows.length / perPage) || 1;
        rows.forEach((r, i) => r.style.display = (i >= (current-1)*perPage && i < current*perPage) ? '' : 'none');
        footer.querySelector('.records-count').textContent = rows.length + ' record(s)';
        footer.querySelector('.page-info').textContent = 'Page ' + current + ' of ' + total;
        footer.querySelector('.btn-prev').disabled = current <= 1;
        footer.querySelector('.btn-next').disabled = current >= total;
    }

    footer.querySelector('.btn-prev')?.addEventListener('click', () => { if (current > 1) { current--; render(); } });
    footer.querySelector('.btn-next')?.addEventListener('click', () => {
        const total = Math.ceil(rows.length / perPage) || 1;
        if (current < total) { current++; render(); }
    });
    render();
}

// Modal helpers
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('DOMContentLoaded', function () {
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
    });
    // Init paginations
    document.querySelectorAll('[data-paginate]').forEach(el => {
        setupPagination(el.id, parseInt(el.dataset.paginate) || 5);
    });
});
