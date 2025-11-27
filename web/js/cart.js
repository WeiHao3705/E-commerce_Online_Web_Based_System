$(document).ready(function() {
    // run initial calculation to set up the totals
    updateOrderSummary();
    
    // handle "Select All" checkbox functionality
    // when user clicks the main checkbox, all item checkboxes follow
    $('#select-all').change(function() {
        $('.item-checkbox').prop('checked', this.checked); // set all item status to checked/unchecked
        updateOrderSummary(); // Recalculate totals after selection change
    });
    
    // handle individual item checkbox changes
    // when user selects/deselects individual items
    $('.item-checkbox').change(function() {
        if (!this.checked) {
            // if any item is unchecked, uncheck "Select All"
            $('#select-all').prop('checked', false);
        } else if ($('.item-checkbox:checked').length === $('.item-checkbox').length) { // if the checked item match the total amount of items in the cart that check the "Select All"
            // if all items are checked, check "Select All"
            $('#select-all').prop('checked', true);
        }
        updateOrderSummary(); // Recalculate totals after selection change
    });
        
    // handle quantity increase button
    // when user clicks the increase button, increase the amount of relative item by 1
    $('.plus-btn').click(function() {
        var display = $(this).siblings('.qty-display'); // Find the quantity display
        var currentVal = parseInt(display.text()); // Get quantity of item in the cart
        if (currentVal < 99) { // set the max available item to purchase to 99
            display.text(currentVal + 1); // Increase by 1
            updateItemTotal($(this).closest('tr')); // Recalculate this row's total
        }
    });
    
    // handle quantity decrease button
    // when user clicks the decrease button, decrease the amount of relative item by 1
    $('.minus-btn').click(function() {
        var display = $(this).siblings('.qty-display'); // Find the quantity display
        var currentVal = parseInt(display.text()); // Get quantity of item in the cart
        if (currentVal > 1) {
            // If quantity > 1, just decrease it
            display.text(currentVal - 1); // Decrease by 1
            updateItemTotal($(this).closest('tr')); // Recalculate this row's total
        } else if (currentVal === 1) {
            // If quantity = 1, ask user if they want to remove item completely
            if(confirm("Are you sure want to remove the item from your cart?")) {
                $(this).closest('tr').remove(); // Remove the entire row
                updateOrderSummary(); // Recalculate all totals
            }
        }
    });
        
    // handle remove button
    // when user clicks the trash icon to remove an item
    $('.remove-btn').click(function() {
        if (confirm('Are you sure you want to remove this item?')) {
            $(this).closest('tr').remove(); // Remove the entire row from table
            updateOrderSummary(); // Recalculate all totals
        }
    });
    
    // handle promo code application
    // when user enters a promo code and clicks Apply
    $('.apply-btn').click(function() {
        var promoCode = $('#promo-code').val(); // Get the entered promo code
        if (promoCode) {
            // TODO: Add actual promo code validation logic here
            alert('Promo code functionality would be implemented here');
        }
    });
});

// function to update individual item total
// This calculates price × quantity for one specific row
function updateItemTotal(row) {
    // Extract price from the price column (remove 'RM ' and commas)
    var price = parseFloat(row.find('.item-price').text().replace('RM ', '').replace(',', ''));
    
    // Extract quantity from the quantity display
    var quantity = parseInt(row.find('.qty-display').text());
    
    // Calculate total for this item
    var total = price * quantity;
    
    // Update the total column for this row
    row.find('.item-total').text('RM ' + total.toFixed(2));
    
    // recalculate the amount after increment or decrement
    updateOrderSummary();
}
    
// master function to recalculate all order totals
// This runs whenever anything changes in the cart
function updateOrderSummary() {
    var subtotal = 0; // Initialize subtotal to zero
    var shippingFee = 15.00; // Fixed shipping fee
    var taxRate = 0.06; // 6% tax rate
    
    // calculate subtotal from ONLY checked items
    // Loop through each checked checkbox to find selected items
    $('.item-checkbox:checked').each(function() {
        var row = $(this).closest('tr'); // Get the table row for this item
        
        // Extract price from price column (remove 'RM ' and commas)
        var price = parseFloat(row.find('.item-price').text().replace('RM ', '').replace(',', ''));
        
        // Extract quantity from quantity display
        var quantity = parseInt(row.find('.qty-display').text());
        
        // Add this item's total to subtotal
        subtotal += price * quantity;
    });
    
    // calculate tax and grand total
    var tax = subtotal * taxRate; // Tax = subtotal × 6%
    var grandTotal = subtotal + shippingFee + tax; // Grand total = subtotal + shipping + tax
    
    // handle case when no items are selected
    if ($('.item-checkbox:checked').length === 0) {
        subtotal = 0;
        tax = 0;
        grandTotal = 0;
        shippingFee = 0; // No shipping if no items
    }
    
    // update the order summary display
    // Find each summary line and update the last span (the amount)
    $('.summary-line').eq(0).find('span:last').text('RM ' + subtotal.toFixed(2)); // Subtotal
    $('.summary-line').eq(1).find('span:last').text('RM ' + shippingFee.toFixed(2)); // Shipping
    $('.summary-line').eq(2).find('span:last').text('RM ' + tax.toFixed(2)); // Tax
    $('.summary-line.total').find('span:last').text('RM ' + grandTotal.toFixed(2)); // Grand Total
    
    // update cart count in navbar
    var totalItems = 0;
    $('.item-checkbox:checked').each(function() {
        var row = $(this).closest('tr');
        var quantity = parseInt(row.find('.qty-display').text());
        totalItems += quantity; // Add quantity of each checked item
    });
    $('#cartCount').text(totalItems); // Update navbar cart badge
    
    // update the cart message dynamically
    $('#cart-item-count').text(totalItems); // Update the number
    $('#item-plural').text(totalItems !== 1 ? 's' : ''); // Update plural form
}