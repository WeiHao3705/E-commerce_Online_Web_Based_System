(function () {
    var modal = document.getElementById('createModal');
    var openBtn = document.getElementById('openCreateModal');
    var closeBtn = document.getElementById('closeCreateModal');
    var cancelBtn = document.getElementById('cancelCreate');

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

    // Delete confirmation modal
    var deleteConfirmModal = document.getElementById('deleteConfirmModal');
    var deleteConfirmMessage = document.getElementById('deleteConfirmMessage');
    var deleteProductName = document.getElementById('deleteProductName');
    var deleteVariantsSection = document.getElementById('deleteVariantsSection');
    var variantItems = document.getElementById('variantItems');
    var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    var cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    var pendingDeleteAction = null;
    var pendingProductId = null;

    function fetchProductVariants(productId) {
        return fetch('AdminProduct.php?action=get_variants&product_id=' + encodeURIComponent(productId))
            .then(function(response) {
                return response.json();
            })
            .catch(function(error) {
                console.error('Error fetching variants:', error);
                return { success: false, variants: [] };
            });
    }

    function showDeleteConfirmation(productId, productName, onConfirm) {
        if (!deleteConfirmModal) return;

        pendingProductId = productId;
        pendingDeleteAction = onConfirm;
        deleteProductName.textContent = productName;

        // Fetch variants
        fetchProductVariants(productId).then(function(result) {
            if (result.success && result.variants && result.variants.length > 0) {
                deleteVariantsSection.style.display = 'block';
                variantItems.innerHTML = '';
                
                result.variants.forEach(function(variant) {
                    var variantBadge = document.createElement('div');
                    variantBadge.style.cssText = 'display: inline-block; background-color: #fee2e2; color: #991b1b; padding: 6px 10px; border-radius: 4px; font-size: 12px; margin-right: 6px; margin-bottom: 6px;';
                    variantBadge.textContent = '● ' + (variant.color || 'Unnamed');
                    variantItems.appendChild(variantBadge);
                });
            } else {
                deleteVariantsSection.style.display = 'none';
                variantItems.innerHTML = '';
            }

            deleteConfirmModal.style.display = 'flex';
        });
    }

    function closeDeleteConfirmation() {
        if (deleteConfirmModal) {
            deleteConfirmModal.style.display = 'none';
        }
        pendingDeleteAction = null;
        pendingProductId = null;
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (pendingDeleteAction) {
                pendingDeleteAction();
            }
            closeDeleteConfirmation();
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', closeDeleteConfirmation);
    }

    if (deleteConfirmModal) {
        deleteConfirmModal.addEventListener('click', function (event) {
            if (event.target === deleteConfirmModal) {
                closeDeleteConfirmation();
            }
        });
    }

    // Handle single product delete
    var deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var row = form.closest('tr');
            var productIdInput = form.querySelector('input[name="product_id"]');
            var productId = productIdInput ? productIdInput.value : 0;
            var productName = row.querySelector('[data-product-name]').getAttribute('data-product-name');
            
            showDeleteConfirmation(productId, productName, function () {
                form.submit();
            });
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
                if (window.__adminShowToast) {
                    window.__adminShowToast('error', 'Error', 'Please select at least one product to delete.');
                } else {
                    alert('Please select at least one product to delete.');
                }
                return;
            }

            var productNames = [];
            checkedBoxes.forEach(function (cb) {
                productNames.push(cb.getAttribute('data-product-name'));
            });

            // For batch delete, show a simplified message (no variants per product)
            if (deleteConfirmModal) {
                deleteConfirmModal.style.display = 'flex';
                deleteProductName.textContent = checkedBoxes.length + ' product(s) selected';
                deleteVariantsSection.style.display = 'none';
                deleteConfirmMessage.textContent = checkedBoxes.length > 3 
                    ? 'Delete: ' + productNames.slice(0, 3).join(', ') + ' and ' + (productNames.length - 3) + ' more?'
                    : 'Delete: ' + productNames.join(', ') + '?';
                
                pendingDeleteAction = function () {
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
                };
            }
        });
    }
})();
