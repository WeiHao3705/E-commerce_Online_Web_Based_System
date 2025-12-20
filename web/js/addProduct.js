// Auto-calculate selling price suggestion if cost and original price are filled
document.addEventListener('DOMContentLoaded', function() {
	const costInput = document.getElementById('cost');
	const originalPriceInput = document.getElementById('original_price');
	const sellingPriceInput = document.getElementById('selling_price');
	const imageInput = document.getElementById('product_images');
	const imagesPreview = document.getElementById('imagesPreview');
	const mainIndexInput = document.getElementById('main_image_index');
	const variantImageInput = document.getElementById('variant_images');
	const variantImagesPreview = document.getElementById('variantImagesPreview');
	const variantMainIndexInput = document.getElementById('variant_main_image_index');
	const productForm = document.getElementById('productForm');
	const variantForm = document.getElementById('variantForm');
	const tabProduct = document.getElementById('tab-product');
	const tabVariant = document.getElementById('tab-variant');

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

	// Shared preview renderer
	function renderPreview(fileInput, previewContainer, mainInput, radioName) {
		const files = Array.from(fileInput.files || []);
		const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

		previewContainer.innerHTML = '';
		if (!files.length) {
			previewContainer.style.display = 'none';
			return;
		}

		let firstValidIndex = -1;

		files.forEach((file, idx) => {
			if (file.size > 5 * 1024 * 1024 || !validTypes.includes(file.type)) {
				return;
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
				radio.name = radioName;
				radio.value = String(idx);

				const mark = document.createElement('span');
				mark.className = 'main-mark';
				mark.textContent = 'Main';

				radio.addEventListener('change', function() {
					mainInput.value = String(idx);
				});

				radioWrap.appendChild(radio);
				radioWrap.appendChild(mark);

				item.appendChild(img);
				item.appendChild(radioWrap);
				previewContainer.appendChild(item);

				if (idx === firstValidIndex) {
					radio.checked = true;
					mainInput.value = String(idx);
				}
			};
			reader.readAsDataURL(file);
		});

		previewContainer.style.display = 'grid';
	}

	// Product images
	imageInput.addEventListener('change', function() {
		renderPreview(imageInput, imagesPreview, mainIndexInput, 'main_image_choice');
	});

	// Variant images
	variantImageInput.addEventListener('change', function() {
		renderPreview(variantImageInput, variantImagesPreview, variantMainIndexInput, 'variant_main_image_choice');
	});

	// Tab switching
	function switchTab(target) {
		if (target === 'product') {
			tabProduct.classList.add('active');
			tabVariant.classList.remove('active');
			productForm.classList.remove('hidden');
			variantForm.classList.add('hidden');
		} else {
			tabVariant.classList.add('active');
			tabProduct.classList.remove('active');
			variantForm.classList.remove('hidden');
			productForm.classList.add('hidden');
		}
	}

	tabProduct.addEventListener('click', () => switchTab('product'));
	tabVariant.addEventListener('click', () => switchTab('variant'));

	// Validate product form
	productForm.addEventListener('submit', function(e) {
		const cost = parseFloat(costInput.value) || 0;
		const original = parseFloat(originalPriceInput.value) || 0;
		const selling = parseFloat(sellingPriceInput.value) || 0;

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
				productForm.submit();
			}
		}
	});

	// Validate variant form
	variantForm.addEventListener('submit', function(e) {
		const productId = document.getElementById('variant_product_id').value;
		const color = document.getElementById('variant_color').value.trim();
		const hasImages = variantImageInput && variantImageInput.files && variantImageInput.files.length > 0;

		if (!productId) {
			e.preventDefault();
			alert('Please choose a product.');
			return;
		}

		if (!color) {
			e.preventDefault();
			alert('Color is required.');
			return;
		}

		if (hasImages && (variantMainIndexInput.value === '' || isNaN(parseInt(variantMainIndexInput.value)))) {
			e.preventDefault();
			alert('Please select one image as the main image.');
			return;
		}
	});
});
