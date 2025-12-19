(function () {
    var modal = document.getElementById('createModal');
    var openBtn = document.getElementById('openCreateModal');
    var closeBtn = document.getElementById('closeCreateModal');
    var cancelBtn = document.getElementById('cancelCreate');

    // Edit modal elements
    var editModal = document.getElementById('editModal');
    var closeEditBtn = document.getElementById('closeEditModal');
    var cancelEditBtn = document.getElementById('cancelEdit');

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function openEditModal() {
        if (editModal) {
            editModal.style.display = 'flex';
        }
    }

    function closeEditModal() {
        if (editModal) {
            editModal.style.display = 'none';
        }
    }

    if (openBtn && modal) {
        openBtn.addEventListener('click', openModal);
    }

    [closeBtn, cancelBtn].forEach(function (btn) {
        if (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                closeModal();
            });
        }
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    // Edit modal event handlers
    [closeEditBtn, cancelEditBtn].forEach(function (btn) {
        if (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                closeEditModal();
            });
        }
    });

    if (editModal) {
        editModal.addEventListener('click', function (event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    }

    // Handle edit button clicks
    var editBtns = document.querySelectorAll('.btn-edit');
    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var productId = this.getAttribute('data-id');
            var productName = this.getAttribute('data-name');
            var category = this.getAttribute('data-category');
            var description = this.getAttribute('data-description');
            var cost = this.getAttribute('data-cost');
            var originalPrice = this.getAttribute('data-original');
            var sellingPrice = this.getAttribute('data-selling');

            // Populate the edit form
            document.getElementById('edit_product_id').value = productId;
            document.getElementById('edit_product_name').value = productName;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_cost').value = cost;
            document.getElementById('edit_original_price').value = originalPrice;
            document.getElementById('edit_selling_price').value = sellingPrice;

            // Open the edit modal
            openEditModal();
        });
    });

    var deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var ok = window.confirm('Delete this product? This cannot be undone.');
            if (!ok) {
                event.preventDefault();
            }
        });
    });
})();
