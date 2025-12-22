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
            var variantCount = parseInt(this.getAttribute('data-variant-count')) || 0;

            // Populate the edit form
            document.getElementById('edit_product_id').value = productId;
            document.getElementById('edit_product_name').value = productName;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_cost').value = cost;
            document.getElementById('edit_original_price').value = originalPrice;
            document.getElementById('edit_selling_price').value = sellingPrice;

            // Handle variant toggle based on existing variants
            var enableVariantToggle = document.getElementById('edit_enable_variant');
            var variantColorGroup = document.getElementById('edit_variant_color_group');
            var variantColorInput = document.getElementById('edit_variant_color');
            var variantStatus = document.getElementById('edit_variant_status');

            if (variantCount > 0) {
                // Product has variants - set toggle ON and make it readonly
                enableVariantToggle.checked = true;
                enableVariantToggle.disabled = true;
                variantColorGroup.style.display = 'none';
                variantColorInput.removeAttribute('required');
                variantColorInput.value = '';
                variantStatus.textContent = 'This product has ' + variantCount + ' variant(s). Variant mode cannot be disabled.';
                variantStatus.style.color = '#667eea';
            } else {
                // Product has no variants - set toggle OFF and make it editable
                enableVariantToggle.checked = false;
                enableVariantToggle.disabled = false;
                variantColorGroup.style.display = 'none';
                variantColorInput.removeAttribute('required');
                variantColorInput.value = '';
                variantStatus.textContent = 'Enable this to create the first variant for this product.';
                variantStatus.style.color = '#6c757d';
            }

            // Open the edit modal
            openEditModal();
        });
    });

    // Handle enable variant checkbox toggle
    var enableVariantCheckbox = document.getElementById('edit_enable_variant');
    var variantColorGroup = document.getElementById('edit_variant_color_group');
    var variantColorInput = document.getElementById('edit_variant_color');

    if (enableVariantCheckbox) {
        enableVariantCheckbox.addEventListener('change', function () {
            if (this.checked) {
                variantColorGroup.style.display = 'block';
                variantColorInput.setAttribute('required', 'required');
            } else {
                variantColorGroup.style.display = 'none';
                variantColorInput.removeAttribute('required');
                variantColorInput.value = '';
            }
        });
    }

    var deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var ok = window.confirm('Delete this product? This cannot be undone.');
            if (!ok) {
                event.preventDefault();
            }
        });
    });

    // Auto-submit filter form when select values change
    var filterForm = document.getElementById('filterForm');
    if (filterForm) {
        var selects = filterForm.querySelectorAll('select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                filterForm.submit();
            });
        });

        // Submit on search input after a brief delay (debounce)
        var searchInput = document.getElementById('filterSearch');
        var searchTimeout = null;
        if (searchInput) {
            searchInput.addEventListener('keyup', function (event) {
                // Submit immediately on Enter key
                if (event.key === 'Enter') {
                    filterForm.submit();
                    return;
                }
                // Debounce for other keys
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });
        }
    }

    // Batch deletion functionality
    var selectAllCheckbox = document.getElementById('selectAllCheckbox');
    var productCheckboxes = document.querySelectorAll('.product-checkbox');
    var bulkActionsSection = document.getElementById('bulkActionsSection');
    var selectedCountSpan = document.getElementById('selectedCount');
    var bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    var clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function updateBulkActionsUI() {
        var checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        var count = checkedBoxes.length;
        
        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }
        
        if (bulkActionsSection) {
            bulkActionsSection.style.display = count > 0 ? 'flex' : 'none';
        }
        
        // Update select all checkbox state
        if (selectAllCheckbox && productCheckboxes.length > 0) {
            selectAllCheckbox.checked = count === productCheckboxes.length;
            selectAllCheckbox.indeterminate = count > 0 && count < productCheckboxes.length;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(function (cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateBulkActionsUI();
        });
    }

    productCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateBulkActionsUI);
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            productCheckboxes.forEach(function (cb) {
                cb.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            updateBulkActionsUI();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            var checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one product to delete.');
                return;
            }

            var productNames = [];
            checkedBoxes.forEach(function (cb) {
                productNames.push(cb.getAttribute('data-product-name'));
            });

            var confirmMsg = 'Are you sure you want to delete ' + checkedBoxes.length + ' product(s)?\n\n' + productNames.join(', ') + '\n\nThis action cannot be undone.';
            if (!confirm(confirmMsg)) {
                return;
            }

            // Create a form and submit for batch deletion
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'AdminProduct.php';

            var actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'batch_delete';
            form.appendChild(actionInput);

            checkedBoxes.forEach(function (cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'product_ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    }
})();
