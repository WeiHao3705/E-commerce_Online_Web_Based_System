// viewProduct.js - Admin Product View Interactivity

document.addEventListener('DOMContentLoaded', function() {
    initImageGallery();
    initImageLightbox();
    initTableSorting();
});

// Initialize image gallery hover effects
function initImageGallery() {
    const imageItems = document.querySelectorAll('.image-item, .variant-image-item');
    
    imageItems.forEach(item => {
        const img = item.querySelector('img');
        if (!img) return;

        // Add error handling for broken images
        img.addEventListener('error', function() {
            this.src = '/web/images/placeholder.png';
            this.alt = 'Image not found';
        });

        // Add loading indicator
        img.addEventListener('load', function() {
            item.classList.add('loaded');
        });
    });
}

// Initialize lightbox for full-size image viewing
function initImageLightbox() {
    const images = document.querySelectorAll('.image-item img, .variant-image-item img');
    
    images.forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function(e) {
            e.preventDefault();
            openLightbox(this.src, this.alt);
        });
    });
}

// Open lightbox modal
function openLightbox(imageSrc, imageAlt) {
    // Create lightbox overlay
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox-overlay';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <button class="lightbox-close">&times;</button>
            <img src="${imageSrc}" alt="${imageAlt}" class="lightbox-image">
            <div class="lightbox-caption">${imageAlt}</div>
        </div>
    `;
    
    // Add to DOM
    document.body.appendChild(lightbox);
    document.body.style.overflow = 'hidden';
    
    // Add styles dynamically
    if (!document.getElementById('lightbox-styles')) {
        const styles = document.createElement('style');
        styles.id = 'lightbox-styles';
        styles.textContent = `
            .lightbox-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.2s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .lightbox-content {
                position: relative;
                max-width: 90%;
                max-height: 90%;
                animation: zoomIn 0.2s ease;
            }
            
            @keyframes zoomIn {
                from { transform: scale(0.8); }
                to { transform: scale(1); }
            }
            
            .lightbox-image {
                max-width: 100%;
                max-height: 85vh;
                display: block;
                border-radius: 8px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            }
            
            .lightbox-close {
                position: absolute;
                top: -40px;
                right: 0;
                background: white;
                border: none;
                color: #333;
                font-size: 32px;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                font-weight: 300;
                line-height: 1;
            }
            
            .lightbox-close:hover {
                background: #f44336;
                color: white;
                transform: rotate(90deg);
            }
            
            .lightbox-caption {
                text-align: center;
                color: white;
                margin-top: 16px;
                font-size: 14px;
                font-weight: 500;
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Close on click outside or close button
    const closeBtn = lightbox.querySelector('.lightbox-close');
    closeBtn.addEventListener('click', closeLightbox);
    
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', handleEscape);
}

// Close lightbox
function closeLightbox() {
    const lightbox = document.querySelector('.lightbox-overlay');
    if (lightbox) {
        lightbox.style.animation = 'fadeOut 0.2s ease';
        setTimeout(() => {
            lightbox.remove();
            document.body.style.overflow = '';
        }, 200);
    }
    document.removeEventListener('keydown', handleEscape);
}

// Handle escape key
function handleEscape(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
}

// Initialize table sorting
function initTableSorting() {
    const table = document.querySelector('.inventory-table');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';
        header.title = 'Click to sort';
        
        header.addEventListener('click', function() {
            sortTable(table, index);
        });
    });
}

// Sort table by column
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determine sort direction
    const currentDirection = table.dataset.sortDirection || 'asc';
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    table.dataset.sortDirection = newDirection;
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try numeric comparison first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return newDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Fall back to string comparison
        return newDirection === 'asc' 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update visual indicator
    updateSortIndicators(table, columnIndex, newDirection);
}

// Update sort direction indicators
function updateSortIndicators(table, columnIndex, direction) {
    const headers = table.querySelectorAll('th');
    headers.forEach((header, index) => {
        // Remove existing indicators
        const existing = header.querySelector('.sort-indicator');
        if (existing) existing.remove();
        
        // Add indicator to sorted column
        if (index === columnIndex) {
            const indicator = document.createElement('span');
            indicator.className = 'sort-indicator';
            indicator.textContent = direction === 'asc' ? ' ▲' : ' ▼';
            indicator.style.fontSize = '10px';
            indicator.style.marginLeft = '4px';
            header.appendChild(indicator);
        }
    });
}

// Utility: Format currency
function formatCurrency(amount) {
    return 'RM ' + parseFloat(amount).toFixed(2);
}

// Utility: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add smooth scroll to top button
function addScrollToTop() {
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '<span class="material-symbols-outlined">arrow_upward</span>';
    scrollBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s;
        z-index: 1000;
    `;
    
    document.body.appendChild(scrollBtn);
    
    // Show/hide on scroll
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });
    
    // Scroll to top on click
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Hover effect
    scrollBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.25)';
    });
    
    scrollBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    });
}

// Initialize scroll to top on load
addScrollToTop();
