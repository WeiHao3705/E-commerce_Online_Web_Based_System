// Initialize Stripe
const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);
const elements = stripe.elements();
const cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#32325d',
            '::placeholder': {
                color: '#aab7c4'
            }
        }
    }
});
cardElement.mount('#card-element');

// Handle card element errors
cardElement.on('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
    } else {
        displayError.textContent = '';
    }
});

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
    
    // Handle payment method selection
    $('input[name="payment"]').change(function() {
        var paymentMethod = $(this).val();
        if (paymentMethod === 'card') {
            $('#card-payment-section').show();
            $('#other-payment-section').hide();
        } else {
            $('#card-payment-section').hide();
            $('#other-payment-section').show();
        }
    });
    
    // Handle place order button click
    $('#placeOrderBtn').click(async function() {
        // Validate delivery address form
        if (!$('#addressForm')[0].checkValidity()) {
            alert('Please fill in all required delivery address fields');
            $('#addressForm')[0].reportValidity();
            return;
        }
        
        // Get selected payment method
        var paymentMethod = $('input[name="payment"]:checked').val();
        
        // Collect address data
        ORDER_DATA.address = $('#address1').val() + ' ' + $('#address2').val();
        ORDER_DATA.city = $('#city').val();
        ORDER_DATA.postcode = $('#postcode').val();
        ORDER_DATA.state = $('#state').val();
        
        // Progress to step 2 (Payment)
        currentStep = 2;
        updateProgressSteps(currentStep);
        
        // Disable button to prevent double submission
        $(this).prop('disabled', true).text('Processing...');
        
        if (paymentMethod === 'card') {
            // Stripe payment flow
            await processStripePayment();
        } else {
            alert('This payment method will be available soon');
            $(this).prop('disabled', false).text('Place Order');
        }
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

// Stripe payment processing function
async function processStripePayment() {
    try {
        // Step 1: Create Payment Intent
        const response = await fetch('create_payment_intent.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                amount: ORDER_DATA.total_amount,
                orderData: ORDER_DATA
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to create payment intent');
        }
        
        // Step 2: Confirm card payment
        const {error, paymentIntent} = await stripe.confirmCardPayment(data.clientSecret, {
            payment_method: {
                card: cardElement
            }
        });
        
        if (error) {
            throw new Error(error.message);
        }
        
        // Step 3: Payment succeeded - save order to database
        if (paymentIntent.status === 'succeeded') {
            const orderResponse = await fetch('process_payment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    paymentIntentId: paymentIntent.id
                })
            });
            
            const orderData = await orderResponse.json();
            
            if (!orderData.success) {
                throw new Error(orderData.error || 'Failed to save order');
            }
            
            // Progress to step 3 (Order Review)
            $('.step').addClass('completed').removeClass('active');
            
            alert('Payment successful! Order ID: ' + orderData.orderId);
            window.location.href = 'order_confirmation.php?order_id=' + orderData.orderId;
        }
        
    } catch (error) {
        alert('Payment failed: ' + error.message);
        $('#placeOrderBtn').prop('disabled', false).text('Place Order');
    }
}
