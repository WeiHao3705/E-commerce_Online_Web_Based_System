// Auto-calculate selling price suggestion if cost and original price are filled
document.addEventListener('DOMContentLoaded', function() {
	const costInput = document.getElementById('cost');
	const originalPriceInput = document.getElementById('original_price');
	const sellingPriceInput = document.getElementById('selling_price');
	const imageInput = document.getElementById('product_image');
	const imagePreview = document.getElementById('imagePreview');
	const previewImg = document.getElementById('previewImg');
	const form = document.querySelector('form');

	function suggestSellingPrice() {
		const cost = parseFloat(costInput.value) || 0;
		const original = parseFloat(originalPriceInput.value) || 0;
		
		if (cost > 0 && original > 0 && !sellingPriceInput.value) {
			// Suggest a price between cost and original, closer to original
			const suggested = Math.round((cost * 0.3 + original * 0.7) * 100) / 100;
			sellingPriceInput.placeholder = suggested.toFixed(2);
		}
	}

	costInput.addEventListener('input', suggestSellingPrice);
	originalPriceInput.addEventListener('input', suggestSellingPrice);

	// Image preview and validation
	imageInput.addEventListener('change', function(e) {
		const file = e.target.files[0];
		if (file) {
			// Validate file size
			if (file.size > 5 * 1024 * 1024) {
				alert('Image size must be less than 5MB.');
				imageInput.value = '';
				imagePreview.style.display = 'none';
				return;
			}

			// Validate file type
			const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
			if (!validTypes.includes(file.type)) {
				alert('Only JPG, PNG, GIF, and WebP images are allowed.');
				imageInput.value = '';
				imagePreview.style.display = 'none';
				return;
			}

			// Show preview
			const reader = new FileReader();
			reader.onload = function(e) {
				previewImg.src = e.target.result;
				imagePreview.style.display = 'block';
			};
			reader.readAsDataURL(file);
		} else {
			imagePreview.style.display = 'none';
		}
	});

	// Validate prices on form submission
	form.addEventListener('submit', function(e) {
		const cost = parseFloat(costInput.value) || 0;
		const original = parseFloat(originalPriceInput.value) || 0;
		const selling = parseFloat(sellingPriceInput.value) || 0;

		if (selling < cost) {
			e.preventDefault();
			alert('Warning: Selling price is lower than cost. You may be selling at a loss.');
			const confirmed = window.confirm('Do you still want to proceed?');
			if (confirmed) {
				form.submit();
			}
		}
	});
});
