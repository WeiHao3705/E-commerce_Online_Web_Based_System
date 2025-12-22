// Auto-calculate selling price suggestion if cost and original price are filled
document.addEventListener('DOMContentLoaded', function() {
	const costInput = document.getElementById('cost');
	const originalPriceInput = document.getElementById('original_price');
	const sellingPriceInput = document.getElementById('selling_price');
	const imagesPreview = document.getElementById('imagesPreview');
	const mainIndexInput = document.getElementById('main_image_index');
	const variantImagesPreview = document.getElementById('variantImagesPreview');
	const variantMainIndexInput = document.getElementById('variant_main_image_index');
	const productForm = document.getElementById('productForm');
	const hasVariantsCheckbox = document.getElementById('has_variants');
	const initialVariantColorInput = document.getElementById('initial_variant_color');
	const initialVariantColorGroup = document.getElementById('initial_variant_color_group');

	const addProductImageBtn = document.getElementById('addProductImageBtn');
	const productImagesInputs = document.getElementById('productImagesInputs');
	const productImagesExcludeInput = document.getElementById('product_images_exclude');
	const addVariantImageBtn = document.getElementById('addVariantImageBtn');
	const variantImagesInputs = document.getElementById('variantImagesInputs');
	const variantImagesExcludeInput = document.getElementById('variant_images_exclude');

	const productExcluded = new Set();
	const variantExcluded = new Set();

	function toggleVariantInputs() {
		if (!hasVariantsCheckbox || !initialVariantColorGroup) return;
		const enabled = !!hasVariantsCheckbox.checked;
		initialVariantColorGroup.style.display = enabled ? 'block' : 'none';
			if (initialVariantColorInput) {
				initialVariantColorInput.required = enabled;
			}
		}

		if (hasVariantsCheckbox) {
			hasVariantsCheckbox.addEventListener('change', toggleVariantInputs);
			toggleVariantInputs();
		}
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

	function renderAggregatePreview(inputsContainer, previewContainer, mainInput, radioName, inputNameAttr, excludedSet, excludeHiddenInput) {
		const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
		const inputs = Array.from(inputsContainer.querySelectorAll('input[name="' + inputNameAttr + '"]'));
		const meta = [];
		let globalIndex = 0;
		inputs.forEach((inp) => {
			const list = Array.from(inp.files || []);
			list.forEach((f) => {
				if (!f) { globalIndex++; return; }
				if (f.size > 5 * 1024 * 1024) { globalIndex++; return; }
				if (!validTypes.includes(f.type)) { globalIndex++; return; }
				meta.push({ file: f, globalIndex });
				globalIndex++;
			});
		});

		// Persist exclude hidden input value
		if (excludeHiddenInput) {
			const values = Array.from(excludedSet.values()).sort((a,b)=>a-b);
			excludeHiddenInput.value = values.join(',');
		}

		previewContainer.innerHTML = '';
		// Build included list indices mapping
		const includedMeta = meta.filter(m => !excludedSet.has(m.globalIndex));
		if (!includedMeta.length) {
			previewContainer.style.display = 'none';
			mainInput.value = '';
			return;
		}

		let selectedIndex = -1;
		const currentMain = parseInt(mainInput.value);
		if (!isNaN(currentMain) && currentMain >= 0 && currentMain < includedMeta.length) {
			selectedIndex = currentMain;
		} else {
			selectedIndex = 0;
			mainInput.value = '0';
		}

		includedMeta.forEach((m, idx) => {
			const reader = new FileReader();
			reader.onload = function(ev) {
				const item = document.createElement('div');
				item.className = 'image-item';
				item.dataset.globalIndex = String(m.globalIndex);
				item.style.position = 'relative';

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

				const removeBtn = document.createElement('button');
				removeBtn.type = 'button';
				removeBtn.className = 'remove-image';
				removeBtn.textContent = '\u00D7';
				removeBtn.title = 'Remove image';
				removeBtn.setAttribute('aria-label', 'Remove image');
				removeBtn.style.position = 'absolute';
				removeBtn.style.top = '4px';
				removeBtn.style.right = '4px';
				removeBtn.style.background = 'rgba(0,0,0,0.6)';
				removeBtn.style.color = '#fff';
				removeBtn.style.border = 'none';
				removeBtn.style.borderRadius = '50%';
				removeBtn.style.width = '24px';
				removeBtn.style.height = '24px';
				removeBtn.style.lineHeight = '20px';
				removeBtn.style.padding = '0';
				removeBtn.style.textAlign = 'center';
				removeBtn.style.cursor = 'pointer';
				removeBtn.addEventListener('click', function() {
					// Mark this global index as excluded and re-render
					excludedSet.add(m.globalIndex);
					// Reset main index if it points beyond new length
					const newIncludedCount = meta.filter(x => !excludedSet.has(x.globalIndex)).length;
					const current = parseInt(mainInput.value);
					if (isNaN(current) || current >= newIncludedCount) {
						mainInput.value = newIncludedCount > 0 ? '0' : '';
					}
					renderAggregatePreview(inputsContainer, previewContainer, mainInput, radioName, inputNameAttr, excludedSet, excludeHiddenInput);
				});

				radioWrap.appendChild(radio);
				radioWrap.appendChild(mark);

				item.appendChild(img);
				item.appendChild(radioWrap);
				item.appendChild(removeBtn);
				previewContainer.appendChild(item);

				if (idx === selectedIndex) {
					radio.checked = true;
				}
			};
			reader.readAsDataURL(m.file);
		});

		previewContainer.style.display = 'grid';
	}

	function createHiddenFileInput(inputNameAttr) {
		const input = document.createElement('input');
		input.type = 'file';
		input.name = inputNameAttr;
		input.accept = 'image/*';
		input.multiple = true;
		input.style.display = 'none';
		return input;
	}

	if (addProductImageBtn && productImagesInputs) {
		addProductImageBtn.addEventListener('click', function() {
			const inp = createHiddenFileInput('product_images[]');
			productImagesInputs.appendChild(inp);
			inp.addEventListener('change', function() {
				if (!inp.files || inp.files.length === 0) {
					productImagesInputs.removeChild(inp);
					return;
				}
				renderAggregatePreview(productImagesInputs, imagesPreview, mainIndexInput, 'main_image_choice', 'product_images[]', productExcluded, productImagesExcludeInput);
			});
			inp.click();
		});
	}

	if (addVariantImageBtn && variantImagesInputs) {
		addVariantImageBtn.addEventListener('click', function() {
			const inp = createHiddenFileInput('variant_images[]');
			variantImagesInputs.appendChild(inp);
			inp.addEventListener('change', function() {
				if (!inp.files || inp.files.length === 0) {
					variantImagesInputs.removeChild(inp);
					return;
				}
				renderAggregatePreview(variantImagesInputs, variantImagesPreview, variantMainIndexInput, 'variant_main_image_choice', 'variant_images[]', variantExcluded, variantImagesExcludeInput);
			});
			inp.click();
		});
	}

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

	productForm.addEventListener('submit', function(e) {
		if (hasVariantsCheckbox && hasVariantsCheckbox.checked) {
			const colorVal = (initialVariantColorInput && initialVariantColorInput.value.trim()) || '';
			if (!colorVal) {
				e.preventDefault();
				alert('Please specify an initial variant color.');
				return;
			}
		}
		const cost = parseFloat(costInput.value) || 0;
		const original = parseFloat(originalPriceInput.value) || 0;
		const selling = parseFloat(sellingPriceInput.value) || 0;

		const productInputs = productImagesInputs ? Array.from(productImagesInputs.querySelectorAll('input[name="product_images[]"]')) : [];
		let productFilesCount = 0;
		productInputs.forEach(inp => { productFilesCount += Array.from(inp.files || []).length; });
		const effectiveProductCount = productFilesCount - productExcluded.size;
		if (effectiveProductCount > 0 && (mainIndexInput.value === '' || isNaN(parseInt(mainIndexInput.value)))) {
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

	variantForm.addEventListener('submit', function(e) {
		const productId = document.getElementById('variant_product_id').value;
		const color = document.getElementById('variant_color').value.trim();

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

		const variantInputs = variantImagesInputs ? Array.from(variantImagesInputs.querySelectorAll('input[name="variant_images[]"]')) : [];
		let variantFilesCount = 0;
		variantInputs.forEach(inp => { variantFilesCount += Array.from(inp.files || []).length; });
		const effectiveVariantCount = variantFilesCount - variantExcluded.size;
		if (effectiveVariantCount > 0 && (variantMainIndexInput.value === '' || isNaN(parseInt(variantMainIndexInput.value)))) {
			e.preventDefault();
			alert('Please select one image as the main image.');
			return;
		}
	});
});
