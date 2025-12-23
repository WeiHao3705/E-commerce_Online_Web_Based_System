// Search Suggestions for Navbar
$(document).ready(function() {
    let searchTimeout;
    const searchInput = $('#searchInput');
    const searchSuggestions = $('#searchSuggestions');
    
    if (!searchInput.length) return;
    
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length < 2) {
            searchSuggestions.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: '/web/views/product/search_suggestions.php',
                type: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(response) {
                    if (response.suggestions && response.suggestions.length > 0) {
                        displaySearchSuggestions(response.suggestions);
                    } else {
                        searchSuggestions.html('<div class="suggestion-item no-results">No results found</div>').show();
                    }
                },
                error: function() {
                    searchSuggestions.hide().empty();
                }
            });
        }, 300);
    });
    
    function displaySearchSuggestions(suggestions) {
        let html = '';
        suggestions.forEach(function(item) {
            const imagePath = item.image_path ? '/' + item.image_path : '/web/images/no-image.png';
            const colorText = item.color ? ' - ' + item.color : '';
            html += `
                <a href="/web/views/product/ProductDetails.php?id=${item.product_id}" class="suggestion-item">
                    <img src="${imagePath}" alt="${item.product_name}">
                    <div class="suggestion-info">
                        <div class="suggestion-name">${item.product_name}${colorText}</div>
                        <div class="suggestion-category">${item.category}</div>
                    </div>
                </a>
            `;
        });
        searchSuggestions.html(html).show();
    }
    
    // Close suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            searchSuggestions.hide();
        }
    });
    
    // Show suggestions on focus if there's a query
    searchInput.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length >= 2 && searchSuggestions.children().length > 0) {
            searchSuggestions.show();
        }
    });
});
