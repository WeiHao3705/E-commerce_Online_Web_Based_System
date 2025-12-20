(() => {
    'use strict';

    // Star rating input handler
    function initStarRatingInput() {
        const starInputs = document.querySelectorAll('.star-rating-input .star');
        
        starInputs.forEach((star, index) => {
            star.addEventListener('mouseenter', () => {
                const rating = index + 1;
                highlightStars(starInputs, rating);
            });
            
            star.addEventListener('mouseleave', () => {
                const selectedRating = getSelectedRating();
                highlightStars(starInputs, selectedRating);
            });
            
            star.addEventListener('click', () => {
                const rating = index + 1;
                setSelectedRating(rating);
                highlightStars(starInputs, rating);
            });
        });
    }
    
    function highlightStars(stars, rating) {
        stars.forEach((star, index) => {
            star.classList.remove('selected', 'hovered');
            // Highlight all stars up to and including the rating (0-indexed, so index < rating means stars 0 to rating-1)
            // For rating 3, we want stars at index 0, 1, 2 to be selected (1st, 2nd, 3rd stars)
            if (index < rating) {
                star.classList.add('selected');
            }
        });
    }
    
    function getSelectedRating() {
        const hiddenInput = document.getElementById('reviewRating');
        return hiddenInput ? parseInt(hiddenInput.value, 10) || 0 : 0;
    }
    
    function setSelectedRating(rating) {
        const hiddenInput = document.getElementById('reviewRating');
        if (hiddenInput) {
            hiddenInput.value = rating;
        }
        
        // Update label
        const ratingLabel = document.querySelector('.rating-label');
        if (ratingLabel) {
            const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
            ratingLabel.textContent = rating > 0 ? labels[rating] : '';
        }
    }
    
    // Display star rating (read-only)
    function renderStarRating(container, rating) {
        if (!container) return;
        
        container.innerHTML = '';
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        
        for (let i = 0; i < 5; i++) {
            const star = document.createElement('span');
            star.className = 'star';
            
            if (i < fullStars) {
                star.classList.add('filled');
                star.textContent = '★';
            } else if (i === fullStars && hasHalfStar) {
                star.classList.add('half');
                star.textContent = '★';
            } else {
                star.textContent = '★';
            }
            
            container.appendChild(star);
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
        const reviewForm = document.getElementById('reviewForm');
        if (!reviewForm) return;
        
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = reviewForm.querySelector('.submit-review-btn');
            const messageDiv = document.getElementById('reviewMessage');
            
            // Validate rating
            const rating = getSelectedRating();
            if (rating < 1 || rating > 5) {
                showMessage(messageDiv, 'Please select a rating', 'error');
                return;
            }
            
            // Get form data
            const formData = new FormData(reviewForm);
            formData.append('action', 'submit');
            
            // Disable submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
            
            try {
                const response = await fetch('../../controller/ReviewController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(messageDiv, result.message || 'Review submitted successfully!', 'success');
                    reviewForm.reset();
                    setSelectedRating(0);
                    highlightStars(document.querySelectorAll('.star-rating-input .star'), 0);
                    
                    // Reload reviews after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(messageDiv, result.message || 'Failed to submit review', 'error');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                showMessage(messageDiv, 'An error occurred. Please try again.', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';
                }
            }
        });
    }
    
    // Show message
    function showMessage(container, message, type) {
        if (!container) return;
        
        container.className = `review-message ${type}`;
        container.textContent = message;
        container.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            container.style.display = 'none';
        }, 5000);
    }
    
    // Handle order item selection
    function initOrderItemSelect() {
        const orderItemSelect = document.getElementById('orderItemSelect');
        const orderIdInput = document.getElementById('reviewOrderId');
        
        if (orderItemSelect && orderIdInput) {
            orderItemSelect.addEventListener('change', (e) => {
                const selectedOption = e.target.options[e.target.selectedIndex];
                if (selectedOption && selectedOption.dataset.orderId) {
                    orderIdInput.value = selectedOption.dataset.orderId;
                } else {
                    orderIdInput.value = '';
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
            const orderItemSelect = document.getElementById('orderItemSelect');
            const orderIdInput = document.getElementById('reviewOrderId');
            const reviewFormContainer = document.querySelector('.review-form-container');
            
            if (orderItemSelect && orderIdInput) {
                // Find and select the matching option
                const options = orderItemSelect.querySelectorAll('option');
                for (let option of options) {
                    if (option.value === reviewOrderItem) {
                        orderItemSelect.value = reviewOrderItem;
                        orderIdInput.value = reviewOrderId;
                        
                        // Trigger change event to update hidden input
                        orderItemSelect.dispatchEvent(new Event('change'));
                        
                        // Scroll to review form
                        if (reviewFormContainer) {
                            setTimeout(() => {
                                reviewFormContainer.scrollIntoView({ 
                                    behavior: 'smooth', 
                                    block: 'start' 
                                });
                            }, 100);
                        }
                        break;
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
        const ratingContainers = document.querySelectorAll('.star-rating[data-rating]');
        ratingContainers.forEach(container => {
            const rating = parseFloat(container.dataset.rating);
            renderStarRating(container, rating);
        });
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

