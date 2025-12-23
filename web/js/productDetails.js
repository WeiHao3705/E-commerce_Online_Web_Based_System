(() => {
    const root = document.getElementById('productDetailRoot');
    if (!root) return;

    const variantSizes = JSON.parse(root.dataset.variantSizes || '{}');
    const variantStock = JSON.parse(root.dataset.variantStock || '{}');
    const loginUrl = root.dataset.loginUrl || '../../account.php';
    const userId = (document.body.dataset.userId || '').trim();
    const hasSizeFlag = (root.dataset.hasSize || '0') === '1';

    // Derived flag: if any sizes exist in the payload, treat as size-enabled even if has_size is missing
    const hasAnySizes = () => {
        return Object.keys(variantSizes || {}).some((vid) => {
            const arr = variantSizes[vid];
            return Array.isArray(arr) && arr.length > 0;
        });
    };
    const mainImage = document.getElementById('mainImage');
    const selectedVariantInput = document.getElementById('selectedVariantId');
    const sizeSelect = document.getElementById('sizeSelect');
    const thumbImages = Array.from(document.querySelectorAll('.thumb-image'));
    const addToCartForm = document.querySelector('form.options-section');
    const stockStatus = document.getElementById('stockStatus');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const wishlistSection = document.getElementById('wishlistSection');
    const sizeSelectElement = document.getElementById('sizeSelect');
    const sizeFormGroup = document.getElementById('sizeFormGroup');
    const quantityInput = document.getElementById('quantityInput');
    // Track current available stock for validation
    let currentAvailableStock = null;
    
    // Named function for size change handler to avoid duplicate listeners
    let sizeChangeHandler = null;

    // Modal elements
    const loginModal = document.getElementById('loginModal');
    const loginModalBackdrop = document.getElementById('loginModalBackdrop');
    const loginModalCancel = document.getElementById('loginModalCancel');
    const loginModalLogin = document.getElementById('loginModalLogin');

    function showToast(message, type = 'success') {
        let container = document.getElementById('pd-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'pd-toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.innerHTML = '<div><div class="title">' + (type === 'success' ? 'Success' : 'Notice') + '</div><div class="msg">' + message + '</div></div>' +
            '<button class="close" aria-label="Close">×</button>';
        
        container.appendChild(toast);
        
        const timer = setTimeout(() => dismiss(), 3000);
        
        function dismiss() {
            if (!toast) return;
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 180);
        }
        
        const closeBtn = toast.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(timer);
                dismiss();
            });
        }
    }

    function toggleSizeVisibility() {
        if (!sizeFormGroup) return;
        // If product explicitly has no sizes AND we have no size data, hide
        if (!hasSizeFlag && !hasAnySizes()) {
            sizeFormGroup.style.display = 'none';
            return;
        }

        // Product has sizes: keep the field visible (even if no options) so user sees status
        sizeFormGroup.style.display = '';
    }

    function updateSizeOptions() {
        if (!sizeSelect || !selectedVariantInput) return;

        const selectedVid = parseInt(selectedVariantInput.value, 10);
        sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';

        // If product is marked as no-size AND we have no size data, hide the control and proceed without sizes
        if (!hasSizeFlag && !hasAnySizes()) {
            if (sizeFormGroup) {
                sizeFormGroup.style.display = 'none';
            }
            sizeSelect.disabled = true;
            // Update stock status based on variant/product without size
            if (!Number.isNaN(selectedVid) && selectedVid) {
                updateStockStatus(selectedVid, null);
            } else {
                updateStockStatus(null, null);
            }
            return;
        }

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
            // No variant → we don't have variant-specific sizes here; ensure visibility toggles
            toggleSizeVisibility();
            return;
        }

        const sizes = variantSizes[selectedVid] || [];
        
        // If product requires sizes but no inventory rows for this variant, mark as out of stock
        if (sizes.length === 0) {
            sizeSelect.innerHTML = '<option value="">-- No sizes available --</option>';
            sizeSelect.disabled = true;
            toggleSizeVisibility();
            updateUIForStock(true, 0, selectedVariantInput ? selectedVariantInput.value : null, null);
            return;
        }

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
        // Toggle size visibility based on available options
        toggleSizeVisibility();
        
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

    function openLoginModal(actionType = 'cart') {
        if (!loginModal) return;
        
        // Update modal message based on action type
        const messageEl = document.getElementById('loginModalMessage');
        if (messageEl) {
            if (actionType === 'wishlist') {
                messageEl.textContent = 'You need to log in to add items to your wishlist.';
            } else {
                messageEl.textContent = 'You need to log in to add items to your cart.';
            }
        }
        
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
        
        // Quantity input: disable if out of stock, set max to available
        if (quantityInput) {
            const available = Number.isFinite(parseInt(stock, 10)) ? parseInt(stock, 10) : null;
            currentAvailableStock = available;
            quantityInput.disabled = isOutOfStock;
            // Update max attribute: when stock is known and > 0, cap to stock; else leave as 0 to prevent submit
            if (!isOutOfStock && available !== null && available > 0) {
                quantityInput.max = String(available);
            } else {
                quantityInput.max = '0';
            }
            // Clamp current value within [1, max] when stock updates
            const maxVal = parseInt(quantityInput.max, 10);
            let currentVal = parseInt(quantityInput.value, 10);
            if (!Number.isFinite(currentVal) || currentVal < 1) currentVal = 1;
            if (Number.isFinite(maxVal) && maxVal > 0) {
                if (currentVal > maxVal) currentVal = maxVal;
            } else {
                // No stock: set to 1 but keep disabled; won't submit
                currentVal = 1;
            }
            quantityInput.value = String(currentVal);
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
        // Only show wishlist button when item is OUT OF STOCK
        if (wishlistSection) {
            if (isOutOfStock) {
                wishlistSection.style.display = 'block';
                const wishlistBtn = document.getElementById('wishlistBtn');
                if (wishlistBtn) {
                    wishlistBtn.setAttribute('data-variant-id', variantId || '');
                    wishlistBtn.setAttribute('data-size', size || '');
                }
            } else {
                // Hide wishlist button when item is in stock
                wishlistSection.style.display = 'none';
            }
        }
    }

    function changeVariant(el) {
        const imagePath = (el.dataset.imagePath || '').trim();
        const vid = el.dataset.variantId;

        // Update selected thumbnail
        thumbImages.forEach((t) => t.classList.remove('selected'));
        el.classList.add('selected');

        if (vid && selectedVariantInput) {
            selectedVariantInput.value = vid;
            
            // Fetch all images for this variant via AJAX
            $.ajax({
                url: '../../controller/ProductController.php',
                method: 'GET',
                data: {
                    action: 'getVariantImages',
                    variant_id: vid
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success && response.images) {
                        updateGalleryImages(response.images);
                    } else {
                        // Fallback to single image update
                        updateSingleImage(imagePath);
                    }
                },
                error: function() {
                    // Fallback to single image update
                    updateSingleImage(imagePath);
                }
            });
            
            updateSizeOptions();
            // Stock status will be updated after sizes are populated
        }
    }
    
    function updateGalleryImages(images) {
        if (!images || images.length === 0) {
            return;
        }
        
        // Find the main images grid container
        const mainImagesGrid = document.querySelector('.main-images-grid');
        if (!mainImagesGrid) {
            return;
        }
        
        // Clear existing images
        mainImagesGrid.innerHTML = '';
        
        // Add new images
        images.forEach((img, idx) => {
            const imgEl = document.createElement('img');
            const src = '/' + (img.image_path || '').replace(/^\/+/u, '');
            imgEl.src = src;
            imgEl.alt = 'Product Image';
            
            if (idx === 0) {
                imgEl.id = 'mainImage';
            } else {
                imgEl.className = 'extra-image';
            }
            
            mainImagesGrid.appendChild(imgEl);
        });
        
        // Update the mainImage reference since we recreated the element
        const newMainImage = document.getElementById('mainImage');
        if (newMainImage && typeof window !== 'undefined') {
            window.productDetailsMainImage = newMainImage;
        }
    }
    
    function updateSingleImage(imagePath) {
        if (imagePath && mainImage) {
            const newSrc = '/' + imagePath.replace(/^\/+/u, '');
            mainImage.src = newSrc;
        }
    }


    function init() {
        thumbImages.forEach((img) => {
            img.addEventListener('click', () => changeVariant(img));
        });

        updateSizeOptions();
        toggleSizeVisibility();

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
                e.preventDefault();
                const selectedVid = selectedVariantInput ? parseInt(selectedVariantInput.value, 10) : null;
                if (selectedVid && variantStock) {
                    const stock = variantStock[selectedVid] || 0;
                    if (stock <= 0) {
                        alert('This variant is currently out of stock. You can add it to your wishlist to be notified when it becomes available.');
                        return;
                    }
                }
                
                // Quantity vs available stock validation
                if (quantityInput) {
                    const maxAttr = parseInt(quantityInput.max, 10);
                    const requested = parseInt(quantityInput.value, 10);
                    const effectiveMax = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : (Number.isFinite(currentAvailableStock) ? currentAvailableStock : null);
                    if (effectiveMax !== null) {
                        if (!Number.isFinite(requested) || requested < 1) {
                            quantityInput.value = '1';
                            alert('Please enter a valid quantity (minimum 1).');
                            return;
                        }
                        if (requested > effectiveMax) {
                            quantityInput.value = String(effectiveMax);
                            alert('Quantity exceeds available stock. Maximum available: ' + effectiveMax);
                            return;
                        }
                    }
                }

                if (!userId) {
                    openLoginModal('cart');
                    return;
                }

                const formData = $(addToCartForm).serialize() + '&ajax=1';
                $.ajax({
                    url: addToCartForm.action,
                    method: 'POST',
                    data: formData,
                    dataType: 'json'
                }).done((resp) => {
                    if (resp && resp.success) {
                        showToast('Item added to cart');
                        setTimeout(() => {
                            window.location.href = 'ProductPage.php';
                        }, 900);
                    } else {
                        alert((resp && resp.message) ? resp.message : 'Failed to add to cart.');
                    }
                }).fail(() => {
                    alert('Failed to add to cart. Please try again.');
                });
            });
        }

        // Clamp quantity on user input/change
        if (quantityInput) {
            const clamp = () => {
                const maxAttr = parseInt(quantityInput.max, 10);
                const maxVal = Number.isFinite(maxAttr) && maxAttr > 0 ? maxAttr : (Number.isFinite(currentAvailableStock) ? currentAvailableStock : 99);
                let val = parseInt(quantityInput.value, 10);
                if (!Number.isFinite(val) || val < 1) val = 1;
                if (Number.isFinite(maxVal)) {
                    if (val > maxVal) val = maxVal;
                }
                quantityInput.value = String(val);
            };
            quantityInput.removeEventListener('input', clamp);
            quantityInput.removeEventListener('change', clamp);
            quantityInput.addEventListener('input', clamp);
            quantityInput.addEventListener('change', clamp);
        }

        // Add login check to wishlist button
        const wishlistBtn = document.getElementById('wishlistBtn');
        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', (e) => {
                if (!userId) {
                    e.preventDefault();
                    openLoginModal('wishlist');
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
