(() => {
    const root = document.getElementById('productDetailRoot');
    if (!root) return;

    const variantSizes = JSON.parse(root.dataset.variantSizes || '{}');
    const variantStock = JSON.parse(root.dataset.variantStock || '{}');
    const loginUrl = root.dataset.loginUrl || '../../account.php';
    const userId = (document.body.dataset.userId || '').trim();
    const mainImage = document.getElementById('mainImage');
    const selectedVariantInput = document.getElementById('selectedVariantId');
    const sizeSelect = document.getElementById('sizeSelect');
    const thumbImages = Array.from(document.querySelectorAll('.thumb-image'));
    const addToCartForm = document.querySelector('form.options-section');
    const stockStatus = document.getElementById('stockStatus');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const wishlistSection = document.getElementById('wishlistSection');
    const sizeSelectElement = document.getElementById('sizeSelect');
    const quantityInput = document.getElementById('quantityInput');

    // Modal elements
    const loginModal = document.getElementById('loginModal');
    const loginModalBackdrop = document.getElementById('loginModalBackdrop');
    const loginModalCancel = document.getElementById('loginModalCancel');
    const loginModalLogin = document.getElementById('loginModalLogin');

    function updateSizeOptions() {
        if (!sizeSelect || !selectedVariantInput) return;

        const selectedVid = parseInt(selectedVariantInput.value, 10);
        sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';

        if (Number.isNaN(selectedVid)) return;

        const sizes = variantSizes[selectedVid] || [];
        sizes.forEach((size) => {
            const opt = document.createElement('option');
            opt.value = size;
            opt.textContent = size;
            sizeSelect.appendChild(opt);
        });

        if (sizes.length > 0) {
            sizeSelect.value = sizes[0];
        }
    }

    function openLoginModal() {
        if (!loginModal) return;
        loginModal.classList.add('open');
        loginModal.setAttribute('aria-hidden', 'false');
    }

    function closeLoginModal() {
        if (!loginModal) return;
        loginModal.classList.remove('open');
        loginModal.setAttribute('aria-hidden', 'true');
    }

    function handleLoginRedirect() {
        window.location.href = loginUrl;
    }

    function updateStockStatus(variantId) {
        if (!variantId || !variantStock) {
            return;
        }

        const vid = parseInt(variantId, 10);
        const stock = variantStock[vid] || 0;
        const isOutOfStock = stock <= 0;

        // Update stock status display
        if (stockStatus) {
            stockStatus.className = 'stock-status';
            if (isOutOfStock) {
                stockStatus.classList.add('out-of-stock');
                stockStatus.innerHTML = '<i class="fas fa-times-circle"></i> <span>Out of Stock</span>';
            } else {
                stockStatus.classList.add('in-stock');
                stockStatus.innerHTML = '<i class="fas fa-check-circle"></i> <span>In Stock (' + stock + ' available)</span>';
            }
        }

        // Update form fields
        if (sizeSelectElement) {
            sizeSelectElement.disabled = isOutOfStock;
        }
        if (quantityInput) {
            quantityInput.disabled = isOutOfStock;
        }

        // Update Add to Cart button
        if (addToCartBtn) {
            if (isOutOfStock) {
                addToCartBtn.type = 'button';
                addToCartBtn.disabled = true;
                addToCartBtn.style.opacity = '0.5';
                addToCartBtn.style.cursor = 'not-allowed';
                addToCartBtn.textContent = 'Out of Stock';
            } else {
                addToCartBtn.type = 'submit';
                addToCartBtn.disabled = false;
                addToCartBtn.style.opacity = '';
                addToCartBtn.style.cursor = '';
                addToCartBtn.textContent = 'Add to Cart';
            }
        }

        // Show/hide wishlist button and update variant_id
        if (wishlistSection) {
            wishlistSection.style.display = isOutOfStock ? 'block' : 'none';
            const wishlistBtn = document.getElementById('wishlistBtn');
            if (wishlistBtn) {
                wishlistBtn.setAttribute('data-variant-id', variantId || '');
            }
        }
    }

    function changeVariant(el) {
        const imagePath = (el.dataset.imagePath || '').trim();
        const vid = el.dataset.variantId;

        if (imagePath && mainImage) {
            const newSrc = '/' + imagePath.replace(/^\/+/u, '');
            mainImage.src = newSrc;
        }

        thumbImages.forEach((t) => t.classList.remove('selected'));
        el.classList.add('selected');

        if (vid && selectedVariantInput) {
            selectedVariantInput.value = vid;
            updateSizeOptions();
            updateStockStatus(vid);
        }
    }

    function init() {
        thumbImages.forEach((img) => {
            img.addEventListener('click', () => changeVariant(img));
        });

        updateSizeOptions();

        // Initialize stock status for default variant
        if (selectedVariantInput && selectedVariantInput.value) {
            updateStockStatus(selectedVariantInput.value);
        } else if (selectedVariantInput) {
            // If no variant selected yet, show default message
            if (stockStatus) {
                stockStatus.className = 'stock-status';
                stockStatus.innerHTML = '<i class="fas fa-info-circle"></i> <span>Select a variant to check availability</span>';
            }
        }

        if (addToCartForm) {
            addToCartForm.addEventListener('submit', (e) => {
                const selectedVid = selectedVariantInput ? parseInt(selectedVariantInput.value, 10) : null;
                if (selectedVid && variantStock) {
                    const stock = variantStock[selectedVid] || 0;
                    if (stock <= 0) {
                        e.preventDefault();
                        alert('This variant is currently out of stock. You can add it to your wishlist to be notified when it becomes available.');
                        return;
                    }
                }
                
                if (!userId) {
                    e.preventDefault();
                    openLoginModal();
                }
            });
        }

        if (loginModalBackdrop) {
            loginModalBackdrop.addEventListener('click', closeLoginModal);
        }

        if (loginModalCancel) {
            loginModalCancel.addEventListener('click', closeLoginModal);
        }

        if (loginModalLogin) {
            loginModalLogin.addEventListener('click', handleLoginRedirect);
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();
