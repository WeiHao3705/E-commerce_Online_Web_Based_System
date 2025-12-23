// Modal helpers
function showDeleteModal(orderIds, isBulk = false) {
    const modal = document.getElementById('deleteModal');
    const text = document.getElementById('deleteModalText');
    const confirmBtn = document.getElementById('deleteModalConfirm');
    modal.style.display = 'flex';
    text.textContent = isBulk
        ? `Are you sure you want to delete the selected ${orderIds.length} order(s)?`
        : 'Are you sure you want to delete this order?';
    confirmBtn.onclick = function() {
        modal.style.display = 'none';
        deleteOrders(orderIds);
    };
}
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllOrders');
    const checkboxes = document.querySelectorAll('.order-checkbox');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!cb.checked) selectAll.checked = false;
                else if ([...checkboxes].every(c => c.checked)) selectAll.checked = true;
            });
        });
    }
    // Bulk delete button (optional, if you want to add a button for bulk delete)
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedIds = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
            if (selectedIds.length === 0) {
                showDeleteModal([], false);
                document.getElementById('deleteModalText').textContent = 'Please select at least one order to delete.';
                document.getElementById('deleteModalConfirm').onclick = function() {
                    document.getElementById('deleteModal').style.display = 'none';
                };
                return;
            }
            showDeleteModal(selectedIds, true);
        });
    }
    // Modal cancel button
    document.getElementById('deleteModalCancel').onclick = function() {
        document.getElementById('deleteModal').style.display = 'none';
    };
    // Close modal on outside click
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
// Single order delete
function deleteOrder(orderId) {
    showDeleteModal([orderId], false);
}
// AJAX call to delete orders (single or multiple)
function deleteOrders(orderIds) {
    fetch('controller/AdminOrderController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', order_ids: orderIds })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            document.getElementById('deleteModalText').textContent = 'Order(s) deleted successfully.';
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteModalConfirm').onclick = function() { location.reload(); };
        } else {
            document.getElementById('deleteModalText').textContent = 'Failed to delete order(s): ' + (data.message || 'Unknown error');
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteModalConfirm').onclick = function() { document.getElementById('deleteModal').style.display = 'none'; };
        }
    })
    .catch(() => {
        document.getElementById('deleteModalText').textContent = 'An error occurred while deleting order(s).';
        document.getElementById('deleteModal').style.display = 'flex';
        document.getElementById('deleteModalConfirm').onclick = function() { document.getElementById('deleteModal').style.display = 'none'; };
    });
}
