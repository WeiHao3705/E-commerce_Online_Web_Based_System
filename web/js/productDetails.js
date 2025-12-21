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
    
    // Named function for size change handler to avoid duplicate listeners
    let sizeChangeHandler = null;

    // Modal elements
    const loginModal = document.getElementById('loginModal');
    const loginModalBackdrop = document.getElementById('loginModalBackdrop');
    const loginModalCancel = document.getElementById('loginModalCancel');
    const loginModalLogin = document.getElementById('loginModalLogin');

    function updateSizeOptions() {
        if (!sizeSelect || !selectedVariantInput) return;

        const selectedVid = parseInt(selectedVariantInput.value, 10);
        sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';

        // Handle products without variants - still need to set up size change handler
        if (Number.isNaN(selectedVid) || !selectedVid) {
            // For products without variants, still set up the size change handler
            if (sizeSelect) {
                if (sizeChangeHandler) {
                    sizeSelect.removeEventListener('change', sizeChangeHandler);
                }
                sizeChangeHandler = function() {
                    const selectedSize = this.value;
                    updateStockStatus(null, selectedSize || null);
                };
                sizeSelect.addEventListener('change', sizeChangeHandler);
            }
            return;
        }

        const sizes = variantSizes[selectedVid] || [];
        
        // Populate all sizes - always enable the dropdown
        sizes.forEach((size) => {
            const opt = document.createElement('option');
            opt.value = size;
            opt.textContent = size;
            sizeSelect.appendChild(opt);
        });

        // Always enable the size dropdown
        sizeSelect.disabled = false;

        if (sizes.length > 0) {
            // Set first size as default, but check its stock
            sizeSelect.value = sizes[0];
            // Check stock for the first size
            if (selectedVariantInput && selectedVariantInput.value) {
                updateStockStatus(selectedVariantInput.value, sizes[0]);
            }
        }
        
        // Add event listener for size change (remove old one first if it exists)
        if (sizeSelect) {
            // Remove old listener if it exists
            if (sizeChangeHandler) {
                sizeSelect.removeEventListener('change', sizeChangeHandler);
            }
            
            // Create new handler
            sizeChangeHandler = function() {
                const selectedSize = this.value;
                const variantId = selectedVariantInput ? selectedVariantInput.value : null;
                if (variantId && selectedSize) {
                    updateStockStatus(variantId, selectedSize);
                } else if (variantId) {
                    // If no size selected, check variant-level stock
                    updateStockStatus(variantId, null);
                } else {
                    // Product without variant - check product-level stock
                    updateStockStatus(null, selectedSize || null);
                }
            };
            
            // Add new listener
            sizeSelect.addEventListener('change', sizeChangeHandler);
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

    function checkSizeStock(variantId, size, callback) {
        if (!variantId || !size) {
            callback(null);
            return;
        }
        
        // Check size-specific stock via AJAX
        $.ajax({
            url: '../../controller/StockController.php',
            method: 'GET',
            data: {
                action: 'getStock',
                variant_id: variantId,
                size: size
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success && response.stock !== undefined) {
                    callback(response.stock);
                } else {
                    callback(null);
                }
            },
            error: function() {
                callback(null);
            }
        });
    }

    function updateStockStatus(variantId, selectedSize) {
        // Handle products without variants
        if (!variantId || variantId === '' || !variantStock) {
            // Product without variants - check product-level stock
            const productId = root.dataset.productId;
            const totalStock = parseInt(root.dataset.totalStock || '0', 10);
            const isOutOfStock = totalStock <= 0;
            updateUIForStock(isOutOfStock, totalStock, null, selectedSize);
            return;
        }

        const vid = parseInt(variantId, 10);
        const stock = variantStock[vid] || 0;
        const isOutOfStock = stock <= 0;

        // If size is selected, check size-specific stock
        if (selectedSize && sizeSelectElement && sizeSelectElement.value) {
            checkSizeStock(vid, selectedSize, function(sizeStock) {
                if (sizeStock !== null) {
                    const isSizeOutOfStock = sizeStock <= 0;
                    updateUIForStock(isSizeOutOfStock, sizeStock, variantId, selectedSize);
                } else {
                    updateUIForStock(isOutOfStock, stock, variantId, selectedSize);
                }
            });
        } else {
            updateUIForStock(isOutOfStock, stock, variantId, null);
        }
    }

    function updateUIForStock(isOutOfStock, stock, variantId, size) {
        // Update stock status display
        if (stockStatus) {
            stockStatus.className = 'stock-status';
            if (isOutOfStock) {
                stockStatus.classList.add('out-of-stock');
                const sizeText = size ? ` (Size: ${size})` : '';
                stockStatus.innerHTML = '<i class="fas fa-times-circle"></i> <span>Out of Stock' + sizeText + '</span>';
            } else {
                stockStatus.classList.add('in-stock');
                const stockText = size ? `Size ${size}: ${stock} available` : `${stock} available`;
                stockStatus.innerHTML = '<i class="fas fa-check-circle"></i> <span>In Stock (' + stockText + ')</span>';
            }
        }

        // NEVER disable the size dropdown - users should always be able to select other sizes
        // The size dropdown should always be enabled
        if (sizeSelectElement) {
            sizeSelectElement.disabled = false;
        }
        
        // Only disable quantity input if the selected size is out of stock
        if (quantityInput) {
            quantityInput.disabled = isOutOfStock;
        }

        // Update Add to Cart button - disable only if the selected size is out of stock
        if (addToCartBtn) {
            if (isOutOfStock) {
                addToCartBtn.type = 'button';
                addToCartBtn.disabled = true;
                addToCartBtn.style.opacity = '0.5';
                addToCartBtn.style.cursor = 'not-allowed';
                if (size) {
                    addToCartBtn.textContent = `Size ${size} Out of Stock`;
                } else {
                    addToCartBtn.textContent = 'Out of Stock';
                }
            } else {
                addToCartBtn.type = 'submit';
                addToCartBtn.disabled = false;
                addToCartBtn.style.opacity = '';
                addToCartBtn.style.cursor = '';
                addToCartBtn.textContent = 'Add to Cart';
            }
        }

        // Show/hide wishlist button and update variant_id and size
        // Always show wishlist button - users can add any product to wishlist
        if (wishlistSection) {
            wishlistSection.style.display = 'block';
            const wishlistBtn = document.getElementById('wishlistBtn');
            if (wishlistBtn) {
                wishlistBtn.setAttribute('data-variant-id', variantId || '');
                wishlistBtn.setAttribute('data-size', size || '');
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
            // Stock status will be updated after sizes are populated
        }
    }

    function init() {
        thumbImages.forEach((img) => {
            img.addEventListener('click', () => changeVariant(img));
        });

        updateSizeOptions();

        // Initialize stock status for default variant or product without variants
        if (selectedVariantInput && selectedVariantInput.value) {
            updateStockStatus(selectedVariantInput.value);
        } else {
            // Product without variants - initialize stock status and show wishlist button
            updateStockStatus(null, null);
            // If no variant and no stock status message, show default message
            if (stockStatus && !stockStatus.innerHTML.includes('Stock')) {
                const totalStock = parseInt(root.dataset.totalStock || '0', 10);
                if (totalStock > 0) {
                    stockStatus.className = 'stock-status in-stock';
                    stockStatus.innerHTML = '<i class="fas fa-check-circle"></i> <span>In Stock (' + totalStock + ' available)</span>';
                } else {
                    stockStatus.className = 'stock-status out-of-stock';
                    stockStatus.innerHTML = '<i class="fas fa-times-circle"></i> <span>Out of Stock</span>';
                }
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
