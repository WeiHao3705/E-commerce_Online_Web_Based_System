$(document).ready(function() {
    // Progress Step Management
    var currentStep = 1;
    
    function updateProgressSteps(step) {
        $('.step').each(function(index) {
            var stepNum = index + 1;
            var $step = $(this);
            
            if (stepNum < step) {
                // Completed steps
                $step.addClass('completed').removeClass('active');
            } else if (stepNum === step) {
                // Current active step
                $step.addClass('active').removeClass('completed');
            } else {
                // Future steps
                $step.removeClass('active completed');
            }
        });
        
        // Animate the lines between steps
        $('.step-line').each(function(index) {
            var $line = $(this);
            if (index < step - 1) {
                $line.addClass('animate');
            } else {
                $line.removeClass('animate');
            }
        });
    }
    
    // Initialize first step as active
    updateProgressSteps(1);
    
    // Handle place order button click
    $('#placeOrderBtn').click(function() {
        // Validate delivery address form
        if (!$('#addressForm')[0].checkValidity()) {
            alert('Please fill in all required delivery address fields');
            $('#addressForm')[0].reportValidity();
            return;
        }
        
        // Get selected payment method
        var paymentMethod = $('input[name="payment"]:checked').val();
        
        // Progress to step 2 (Payment)
        currentStep = 2;
        updateProgressSteps(currentStep);
        
        // Simulate payment processing
        setTimeout(function() {
            // Progress to step 3 (Order Review)
            currentStep = 3;
            updateProgressSteps(currentStep);
            
            // Confirm order after showing review step
            setTimeout(function() {
                if (confirm('Confirm order placement?')) {
                    // Mark all steps as completed
                    $('.step').addClass('completed').removeClass('active');
                    alert('Order placed successfully! Payment method: ' + paymentMethod);
                    // TODO: Redirect to order confirmation page
                    // window.location.href = 'order-confirmation.php';
                }
            }, 1000);
        }, 1500);
    });
    
    // Add visual feedback for payment method selection
    $('.payment-option input[type="radio"]').change(function() {
        $('.payment-card').removeClass('selected');
        $(this).closest('.payment-option').find('.payment-card').addClass('selected');
    });
    
    // Mark initially selected payment method
    $('.payment-option input[type="radio"]:checked').closest('.payment-option').find('.payment-card').addClass('selected');

    $('#default-address').change(function() {
        if($(this).is(':checked')) {
            console.log('Checkbox checked - fetching address...');
            // fetch all information about the default address
            $.ajax({
                url: 'get_default_address.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('=== AJAX Response Received ===');
                    console.log('Full response:', response);
                    console.log('Success status:', response.success);
                    console.log('Error message:', response.error);
                    console.log('Full Name:', response.fullName);
                    console.log('Address1:', response.address1);
                    console.log('==============================');
                    
                    if(response.success) {
                        console.log('SUCCESS: Filling form fields...');
                        $('#fullName').val(response.fullName);
                        $('#phone').val(response.phone);
                        $('#email').val(response.email);
                        $('#address1').val(response.address1);
                        $('#address2').val(response.address2);
                        $('#city').val(response.city);
                        $('#state').val(response.state);
                        $('#postcode').val(response.postcode);
                    } else {
                        console.log('FAILURE: No address found');
                        console.log('Error message from server:', response.error);
                        alert('No default address found: ' + (response.error || 'Unknown error'));
                        $('#default-address').prop('checked', false);
                        $('#addressForm')[0].reset();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('=== AJAX Error ===');
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Response Text:', xhr.responseText);
                    console.log('==================');
                    alert('Error fetching default address: ' + error);
                    $('#default-address').prop('checked', false);
                    $('#addressForm')[0].reset();
                }
            });
        } else {
            $('#addressForm')[0].reset();
        }
    });
});
