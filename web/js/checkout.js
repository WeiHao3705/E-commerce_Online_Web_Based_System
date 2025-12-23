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
        showModalMessage(event.error.message);
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
            $('#online-banking-section').hide();
            $('#e-wallet-section').hide();
            $('#other-payment-section').hide();
        } else if (paymentMethod === 'online-banking') {
            $('#card-payment-section').hide();
            $('#online-banking-section').show();
            $('#e-wallet-section').hide();
            $('#other-payment-section').hide();
        } else if (paymentMethod === 'e-wallet') {
            $('#card-payment-section').hide();
            $('#online-banking-section').hide();
            $('#e-wallet-section').show();
            $('#other-payment-section').hide();
        } else {
            $('#card-payment-section').hide();
            $('#online-banking-section').hide();
            $('#e-wallet-section').hide();
            $('#other-payment-section').show();
        }
    });
    
    // Handle place order button click
    $('#placeOrderBtn').click(async function() {
    // 1. Validate Form
    if (!$('#addressForm')[0].checkValidity()) {
        showModalMessage('Please fill in all required delivery address fields');
        $('#addressForm')[0].reportValidity();
        return;
    }

    // 2. Disable button
    $(this).prop('disabled', true).text('Processing...');

    // 3. Get Payment Method
    var paymentMethod = $('input[name="payment"]:checked').val();

    // 4. Update the global ORDER_DATA with the form values
    ORDER_DATA.address = $('#address1').val() + ' ' + $('#address2').val();
    ORDER_DATA.city = $('#city').val();
    ORDER_DATA.postcode = $('#postcode').val();
    ORDER_DATA.state = $('#state').val();
    ORDER_DATA.fullName = $('#fullName').val();
    ORDER_DATA.phone = $('#phone').val();
    ORDER_DATA.email = $('#email').val();

    // 5. Progress Step
    updateProgressSteps(2);

    // 6. Proceed to Payment based on method
    if (paymentMethod === 'card') {
        await processStripePayment();
    } else {
        // Handle Online Banking / E-Wallet
        $.ajax({
            url: 'process_payment.php', // This should handle updating address + status
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                paymentMethod: paymentMethod,
                orderId: ORDER_DATA.orderId,
                addressData: ORDER_DATA // Send the address details here!
            }),
            success: function(response) {
                window.location.href = 'order_confirmation.php?order_id=' + ORDER_DATA.orderId;
            },
            error: function() {
                showModalMessage('Payment failed. Please try again.');
                $('#placeOrderBtn').prop('disabled', false).text('Place Order');
            }
        });
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
        // Step 0: Check if card fields are empty (by checking for errors or empty input)
        // Stripe hides the real input, so we rely on Stripe's validation and the card-errors div
        const cardErrorText = document.getElementById('card-errors').textContent;
        if (cardErrorText) {
            showModalMessage(cardErrorText);
            $('#placeOrderBtn').prop('disabled', false).text('Place Order');
            return;
        }

        // Step 1: Create Payment Intent
        const response = await fetch('create_payment_intent.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                orderId: ORDER_DATA.orderId,
                amount: ORDER_DATA.total_amount,
                orderData: ORDER_DATA
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            showModalMessage(data.error || 'Failed to create payment intent');
            throw new Error(data.error || 'Failed to create payment intent');
        }
        
        // Step 2: Confirm card payment
        const {error, paymentIntent} = await stripe.confirmCardPayment(data.clientSecret, {
            payment_method: {
                card: cardElement
            }
        });
        
        if (error) {
            showModalMessage(error.message);
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
                showModalMessage(orderData.error || 'Failed to save order');
                throw new Error(orderData.error || 'Failed to save order');
            }
            
            // Progress to step 3 (Order Review)
            $('.step').addClass('completed').removeClass('active');
            
            showModalMessage('Payment successful! Order ID: ' + orderData.orderId);
            setTimeout(function() {
                window.location.href = 'order_confirmation.php?order_id=' + orderData.orderId;
            }, 1500);
        }
        
    } catch (error) {
        showModalMessage('Payment failed: ' + error.message + '<br>Your order is saved as pending. Please try again.');
        $('#placeOrderBtn').prop('disabled', false).text('Place Order');
    }
}
