// Wishlist functionality
class Wishlist {
    constructor() {
        // Use global controller URL if available (set by page), otherwise use relative path
        this.baseUrl = window.wishlistControllerUrl || '../../controller/WishlistController.php';
        this.init();
    }

    init() {
        this.updateWishlistCount();
        this.attachEventListeners();
    }

    attachEventListeners() {
        // Wishlist button clicks
        $(document).on('click', '.wishlist-btn, .add-to-wishlist', (e) => {
            e.preventDefault();
            const button = $(e.currentTarget);
            
            // Don't proceed if button is disabled
            if (button.prop('disabled')) {
                return;
            }
            
            const productId = button.data('product-id');
            if (productId) {
                this.toggleWishlist(productId, button);
            }
        });
    }

    async toggleWishlist(productId, button) {
        try {
            // Check if button is disabled (should not happen, but safety check)
            if ($(button).prop('disabled')) {
                return;
            }

            // Check current state
            const isInWishlist = $(button).hasClass('in-wishlist');
            const action = isInWishlist ? 'remove' : 'add';

            // Get variant_id and size from button if available
            const variantId = $(button).data('variant-id') || null;
            const size = $(button).data('size') || null;
            const data = { action, product_id: productId };
            if (variantId) {
                data.variant_id = variantId;
            }
            if (size) {
                data.size = size;
            }

            const response = await $.ajax({
                url: this.baseUrl,
                method: 'POST',
                data: data,
                dataType: 'json'
            });

            if (response.success) {
                // Update button state
                $(button).toggleClass('in-wishlist');
                
                // Update icon
                const icon = $(button).find('i');
                if (isInWishlist) {
                    icon.removeClass('fa-solid').addClass('fa-regular');
                    $(button).find('span').text('Add to Wishlist');
                } else {
                    icon.removeClass('fa-regular').addClass('fa-solid');
                    $(button).find('span').text('Remove from Wishlist');
                }

                // Update count
                this.updateWishlistCount();

                // Show notification
                this.showNotification(response.message, 'success');
            } else {
                if (response.message === 'Please login to manage wishlist') {
                    if (confirm('Please login to add items to wishlist. Go to login page?')) {
                        window.location.href = 'views/security/login.php';
                    }
                } else {
                    this.showNotification(response.message, 'error');
                }
            }
        } catch (error) {
            console.error('Wishlist error:', error);
            if (error.status === 401) {
                if (confirm('Please login to add items to wishlist. Go to login page?')) {
                    window.location.href = 'views/security/login.php';
                }
            } else {
                this.showNotification('Error updating wishlist', 'error');
            }
        }
    }

    async updateWishlistCount() {
        try {
            const response = await $.ajax({
                url: this.baseUrl + '?action=count',
                method: 'GET',
                dataType: 'json'
            });

            if (response.success) {
                $('.wishlist-count').text(response.count);
                
                // Update badge visibility
                if (response.count > 0) {
                    $('.wishlist-count').show();
                } else {
                    $('.wishlist-count').hide();
                }
            }
        } catch (error) {
            console.error('Error updating wishlist count:', error);
        }
    }

    async checkWishlistStatus(productId) {
        try {
            const response = await $.ajax({
                url: this.baseUrl + '?action=check&product_id=' + productId,
                method: 'GET',
                dataType: 'json'
            });

            return response.success && response.inWishlist;
        } catch (error) {
            console.error('Error checking wishlist status:', error);
            return false;
        }
    }

    showNotification(message, type) {
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        const bgColor = type === 'success' ? '#10b981' : '#ef4444';

        const notification = $(`
            <div class="wishlist-notification" style="
                position: fixed;
                top: 80px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            ">
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => {
            notification.css('animation', 'slideOut 0.3s ease');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialize wishlist when DOM is ready
$(document).ready(function() {
    window.wishlistManager = new Wishlist();
});

// Add CSS animations
if (!document.getElementById('wishlist-animations')) {
    const style = document.createElement('style');
    style.id = 'wishlist-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        .wishlist-btn, .add-to-wishlist {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .wishlist-btn.in-wishlist i,
        .add-to-wishlist.in-wishlist i {
            color: #FF523B;
        }
    `;
    document.head.appendChild(style);
}
