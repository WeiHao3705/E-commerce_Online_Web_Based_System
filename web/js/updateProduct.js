/**
 * UpdateProduct.js - Product update and variant management functionality
 */

(function() {
    'use strict';

    // Toast notification system
    function showToast(type, title, message) {
        var container = document.getElementById('toastContainer');
        if (!container) return;
        
        var el = document.createElement('div');
        el.className = 'toast ' + (type || 'success');
        el.innerHTML = '<div><div class="title">' + (title || 'Notice') + '</div><div class="msg">' + (message || '') + '</div></div>' +
            '<button class="close" aria-label="Close">×</button>';
        container.appendChild(el);
        
        var timer = setTimeout(function() { dismiss(); }, 3500);
        
        function dismiss() {
            if (!el) return;
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function() {
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 180);
        }
        
        el.querySelector('.close').addEventListener('click', function() {
            clearTimeout(timer);
            dismiss();
        });
    }

    // Expose for server-side flash messages
    window.__adminShowToast = showToast;

    // Variant toggle functionality
    var enableVariantCheckbox = document.getElementById('enable_variant');
    var variantColorGroup = document.getElementById('variant_color_group');
    var variantColorInput = document.getElementById('variant_color');

    if (enableVariantCheckbox) {
        enableVariantCheckbox.addEventListener('change', function() {
            var on = this.checked;
            variantColorGroup.style.display = on ? 'block' : 'none';
            if (on) {
                variantColorInput.setAttribute('required', 'required');
            } else {
                variantColorInput.removeAttribute('required');
                variantColorInput.value = '';
            }
        });
    }

    // Delete Variant Modal
    var deleteVariantModal = document.getElementById('deleteVariantModal');
    var deleteVariantColorEl = document.getElementById('deleteVariantColor');
    var confirmDeleteVariantBtn = document.getElementById('confirmDeleteVariantBtn');
    var cancelDeleteVariantBtn = document.getElementById('cancelDeleteVariantBtn');
    var currentVariantForm = null;

    document.querySelectorAll('.delete-variant-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var variantColor = this.getAttribute('data-variant-color');
            deleteVariantColorEl.textContent = variantColor || 'Unknown';
            currentVariantForm = this.closest('form');
            deleteVariantModal.style.display = 'flex';
        });
    });

    cancelDeleteVariantBtn.addEventListener('click', function() {
        deleteVariantModal.style.display = 'none';
        currentVariantForm = null;
    });

    confirmDeleteVariantBtn.addEventListener('click', function() {
        if (currentVariantForm) {
            currentVariantForm.submit();
        }
    });

    deleteVariantModal.addEventListener('click', function(e) {
        if (e.target === deleteVariantModal) {
            deleteVariantModal.style.display = 'none';
            currentVariantForm = null;
        }
    });

    // Delete Image Modal
    var deleteImageModal = document.getElementById('deleteImageModal');
    var deleteImageNameEl = document.getElementById('deleteImageName');
    var confirmDeleteImageBtn = document.getElementById('confirmDeleteImageBtn');
    var cancelDeleteImageBtn = document.getElementById('cancelDeleteImageBtn');
    var currentImageForm = null;

    document.querySelectorAll('.delete-image-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var imageName = this.getAttribute('data-image-name');
            deleteImageNameEl.textContent = imageName || 'Unknown';
            currentImageForm = this.closest('form');
            deleteImageModal.style.display = 'flex';
        });
    });

    cancelDeleteImageBtn.addEventListener('click', function() {
        deleteImageModal.style.display = 'none';
        currentImageForm = null;
    });

    confirmDeleteImageBtn.addEventListener('click', function() {
        if (currentImageForm) {
            currentImageForm.submit();
        }
    });

    deleteImageModal.addEventListener('click', function(e) {
        if (e.target === deleteImageModal) {
            deleteImageModal.style.display = 'none';
            currentImageForm = null;
        }
    });
})();
