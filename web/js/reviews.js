$(document).ready(function() {
    'use strict';

    // Star rating input handler
    function initStarRatingInput() {
        $('.star-rating-input .star').each(function(index) {
            const $star = $(this);
            
            $star.on('mouseenter', function() {
                const rating = index + 1;
                highlightStars($('.star-rating-input .star'), rating);
            });
            
            $star.on('mouseleave', function() {
                const selectedRating = getSelectedRating();
                highlightStars($('.star-rating-input .star'), selectedRating);
            });
            
            $star.on('click', function() {
                const rating = index + 1;
                setSelectedRating(rating);
                highlightStars($('.star-rating-input .star'), rating);
            });
        });
    }
    
    function highlightStars($stars, rating) {
        $stars.each(function(index) {
            const $star = $(this);
            $star.removeClass('selected hovered');
            // Highlight all stars up to and including the rating (0-indexed, so index < rating means stars 0 to rating-1)
            // For rating 3, we want stars at index 0, 1, 2 to be selected (1st, 2nd, 3rd stars)
            if (index < rating) {
                $star.addClass('selected');
            }
        });
    }
    
    function getSelectedRating() {
        const $hiddenInput = $('#reviewRating');
        return $hiddenInput.length ? parseInt($hiddenInput.val(), 10) || 0 : 0;
    }
    
    function setSelectedRating(rating) {
        const $hiddenInput = $('#reviewRating');
        if ($hiddenInput.length) {
            $hiddenInput.val(rating);
        }
        
        // Update label
        const $ratingLabel = $('.rating-label');
        if ($ratingLabel.length) {
            const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
            $ratingLabel.text(rating > 0 ? labels[rating] : '');
        }
    }
    
    // Display star rating (read-only)
    function renderStarRating($container, rating) {
        if (!$container.length) return;
        
        $container.empty();
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 0; i < 5; i++) {
            const $star = $('<span>').addClass('star').text('★');
            
            if (i < fullStars) {
                $star.addClass('filled');
            } else if (i === fullStars && hasHalfStar) {
                $star.addClass('half');
            }
            
            $container.append($star);
        }
    }
    
    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Submit review form
    function initReviewForm() {
        const $reviewForm = $('#reviewForm');
        if (!$reviewForm.length) return;
        
        $reviewForm.on('submit', function(e) {
            e.preventDefault();
            
            const $submitBtn = $reviewForm.find('.submit-review-btn');
            const $messageDiv = $('#reviewMessage');
            
            // Validate rating
            const rating = getSelectedRating();
            if (rating < 1 || rating > 5) {
                showMessage($messageDiv, 'Please select a rating', 'error');
                return;
            }
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'submit');
            
            // Disable submit button
            if ($submitBtn.length) {
                $submitBtn.prop('disabled', true);
                $submitBtn.text('Submitting...');
            }
            
            $.ajax({
                url: '../../controller/ReviewController.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        showMessage($messageDiv, result.message || 'Review submitted successfully!', 'success');
                        $reviewForm[0].reset();
                        setSelectedRating(0);
                        highlightStars($('.star-rating-input .star'), 0);
                        
                        // Reload reviews after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage($messageDiv, result.message || 'Failed to submit review', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error submitting review:', error);
                    showMessage($messageDiv, 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    if ($submitBtn.length) {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.text('Submit Review');
                    }
                }
            });
        });
    }
    
    // Show message
    function showMessage($container, message, type) {
        if (!$container.length) return;
        
        $container.removeClass('success error').addClass('review-message ' + type);
        $container.text(message);
        $container.show();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $container.hide();
        }, 5000);
    }
    
    // Handle order item selection
    function initOrderItemSelect() {
        const $orderItemSelect = $('#orderItemSelect');
        const $orderIdInput = $('#reviewOrderId');
        
        if ($orderItemSelect.length && $orderIdInput.length) {
            $orderItemSelect.on('change', function() {
                const $selectedOption = $(this).find('option:selected');
                if ($selectedOption.length && $selectedOption.data('order-id')) {
                    $orderIdInput.val($selectedOption.data('order-id'));
                } else {
                    $orderIdInput.val('');
                }
            });
        }
    }
    
    // Handle URL parameters for pre-selecting order item
    function initUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const reviewOrderItem = urlParams.get('review_order_item');
        const reviewOrderId = urlParams.get('review_order_id');
        
        if (reviewOrderItem && reviewOrderId) {
            const $orderItemSelect = $('#orderItemSelect');
            const $orderIdInput = $('#reviewOrderId');
            const $reviewFormContainer = $('.review-form-container');
            
            if ($orderItemSelect.length && $orderIdInput.length) {
                // Find and select the matching option
                const $option = $orderItemSelect.find('option[value="' + reviewOrderItem + '"]');
                if ($option.length) {
                    $orderItemSelect.val(reviewOrderItem);
                    $orderIdInput.val(reviewOrderId);
                    
                    // Trigger change event to update hidden input
                    $orderItemSelect.trigger('change');
                    
                    // Scroll to review form
                    if ($reviewFormContainer.length) {
                        setTimeout(function() {
                            $('html, body').animate({
                                scrollTop: $reviewFormContainer.offset().top - 100
                            }, 500);
                        }, 100);
                    }
                }
            }
        }
    }
    
    // Initialize all review-related functionality
    function init() {
        initStarRatingInput();
        initReviewForm();
        initOrderItemSelect();
        initUrlParameters();
        
        // Render existing star ratings
        $('.star-rating[data-rating]').each(function() {
            const $container = $(this);
            const rating = parseFloat($container.data('rating'));
            renderStarRating($container, rating);
        });
    }
    
    // Initialize when document is ready
    init();
});
