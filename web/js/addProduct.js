// Auto-calculate selling price suggestion if cost and original price are filled
document.addEventListener('DOMContentLoaded', function() {
	const costInput = document.getElementById('cost');
	const originalPriceInput = document.getElementById('original_price');
	const sellingPriceInput = document.getElementById('selling_price');
	const imageInput = document.getElementById('product_images');
	const imagesPreview = document.getElementById('imagesPreview');
	const mainIndexInput = document.getElementById('main_image_index');
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

	// Multiple image preview and validation with main selection
	imageInput.addEventListener('change', function(e) {
		const files = Array.from(e.target.files || []);
		const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

		imagesPreview.innerHTML = '';
		if (!files.length) {
			imagesPreview.style.display = 'none';
			return;
		}

		let firstValidIndex = -1;

		files.forEach((file, idx) => {
			// Validate file size/type
			if (file.size > 5 * 1024 * 1024 || !validTypes.includes(file.type)) {
				return; // skip invalid
			}
			if (firstValidIndex === -1) firstValidIndex = idx;

			const reader = new FileReader();
			reader.onload = function(ev) {
				const item = document.createElement('div');
				item.className = 'image-item';

				const img = document.createElement('img');
				img.src = ev.target.result;
				img.alt = 'Preview ' + (idx + 1);

				const radioWrap = document.createElement('label');
				radioWrap.className = 'main-select';
				radioWrap.title = 'Set as main image';

				const radio = document.createElement('input');
				radio.type = 'radio';
				radio.name = 'main_image_choice';
				radio.value = String(idx);

				const mark = document.createElement('span');
				mark.className = 'main-mark';
				mark.textContent = 'Main';

				radio.addEventListener('change', function() {
					mainIndexInput.value = String(idx);
				});

				radioWrap.appendChild(radio);
				radioWrap.appendChild(mark);

				item.appendChild(img);
				item.appendChild(radioWrap);
				imagesPreview.appendChild(item);

				// Default first valid as main
				if (idx === firstValidIndex) {
					radio.checked = true;
					mainIndexInput.value = String(idx);
				}
			};
			reader.readAsDataURL(file);
		});

		imagesPreview.style.display = 'grid';
	});

	// Validate on form submission
	form.addEventListener('submit', function(e) {
		const cost = parseFloat(costInput.value) || 0;
		const original = parseFloat(originalPriceInput.value) || 0;
		const selling = parseFloat(sellingPriceInput.value) || 0;

		// If images selected, ensure a main is chosen
		const hasImages = imageInput && imageInput.files && imageInput.files.length > 0;
		if (hasImages && (mainIndexInput.value === '' || isNaN(parseInt(mainIndexInput.value)))) {
			e.preventDefault();
			alert('Please select one image as the main image.');
			return;
		}

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
