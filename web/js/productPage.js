/**
 * Product Filter AJAX Module
 * Handles real-time filtering of products without page reload
 */

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const filterInputs = document.querySelectorAll('.filter-input');
    const productsSection = document.querySelector('.products-section');
    let filterTimeout;

    /**
     * Apply filters via AJAX and update the products grid
     */
    function applyFiltersAJAX() {
        // Get form data
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        
        // Add action parameter
        params.append('action', 'getFilteredProducts');

        // Show loading state
        productsSection.style.opacity = '0.6';
        productsSection.style.pointerEvents = 'none';

        // Make AJAX request
        fetch('ProductPage.php?' + params.toString())
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update products grid
                    updateProductsGrid(data.grouped);
                    // Update URL without reloading
                    window.history.pushState({}, '', 'ProductPage.php?' + params.toString());
                } else {
                    console.error('Filter error:', data.error);
                    alert('Error applying filters: ' + data.error);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('Error applying filters. Please try again.');
            })
            .finally(() => {
                // Remove loading state
                productsSection.style.opacity = '1';
                productsSection.style.pointerEvents = 'auto';
            });
    }

    /**
     * Update the products grid with filtered results
     * @param {Object} grouped - Products grouped by category
     */
    function updateProductsGrid(grouped) {
        if (Object.keys(grouped).length === 0) {
            // No products found
            productsSection.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <p style="font-size: 16px; margin: 0;">No products found matching your filters.</p>
                </div>
            `;
            return;
        }

        let html = '';
        for (const [category, products] of Object.entries(grouped)) {
            html += `
                <section class="category-section">
                    <div class="category-heading">
                        <h3>${escapeHtml(category)}</h3>
                        <span class="category-count">${products.length} item(s)</span>
                    </div>
                    <div class="product-grid">
            `;

            products.forEach(product => {
                const imagePath = product.image_path ? `/${escapeHtml(product.image_path)}` : '';
                const price = product.original_price ? `RM ${parseFloat(product.original_price).toFixed(2)}` : 'Price unavailable';
                const colors = product.colors ? escapeHtml(product.colors) : 'No variants';

                html += `
                    <a class="product-card" href="ProductDetails.php?id=${product.product_id}">
                        <div class="product-media">
                            ${imagePath ? `<img src="${imagePath}" alt="${escapeHtml(product.product_name)}">` : ''}
                        </div>
                        <div class="product-body">
                            <h4 class="product-title">${escapeHtml(product.product_name)}</h4>
                            <div class="product-price">${price}</div>
                            <div class="product-meta">
                                <strong>Colors:</strong> ${colors}
                            </div>
                        </div>
                    </a>
                `;
            });

            html += `
                    </div>
                </section>
            `;
        }

        productsSection.innerHTML = html;
    }

    /**
     * Escape HTML special characters to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialize event listeners for filter inputs
     */
    function initializeFilterListeners() {
        filterInputs.forEach(input => {
            // Listen to change event for checkboxes
            input.addEventListener('change', function() {
                clearTimeout(filterTimeout);
                // Debounce the filter application (300ms delay)
                filterTimeout = setTimeout(applyFiltersAJAX, 300);
            });

            // For price inputs, also listen to input event for real-time feedback
            if (input.type === 'number') {
                input.addEventListener('input', function() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(applyFiltersAJAX, 500);
                });
            }
        });

        // Prevent form submission
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFiltersAJAX();
        });
    }

    // Initialize the filter listeners
    initializeFilterListeners();
});
