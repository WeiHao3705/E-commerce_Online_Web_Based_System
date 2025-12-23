(function(){
  const form = document.getElementById('restockForm');
  if(!form){return;}
  const productSelect = document.getElementById('productSelect');
  const variantSelect = document.getElementById('variantSelect');
  const sizeSelect = document.getElementById('sizeSelect');
  const sizeFormGroup = sizeSelect ? sizeSelect.closest('.form-group') : null;
  const newSizeToggle = document.getElementById('newSizeToggle');
  const sizeNewInput = document.getElementById('sizeNew');
  const sizeOption = document.getElementById('sizeOption');

  const variantsMap = JSON.parse(form.dataset.variants || '{}');
  const sizesByProduct = JSON.parse(form.dataset.sizesProduct || '{}');
  const sizesByVariant = JSON.parse(form.dataset.sizesVariant || '{}');
  const hasSizeMap = JSON.parse(form.dataset.hasSize || '{}');

  const requiresSize = (pid) => {
    if(!pid) return false;
    const val = hasSizeMap[pid];
    return String(val) === '1';
  };

  const clearSelect = (sel, placeholder) => {
    sel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder || '-- Select --';
    sel.appendChild(opt);
  };

  const populateVariants = (pid) => {
    clearSelect(variantSelect, '-- No variant --');
    variantSelect.disabled = true;
    const list = variantsMap[pid] || [];
    if(list.length){
      variantSelect.disabled = false;
      clearSelect(variantSelect, '-- Select Variant --');
      list.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.variant_id;
        opt.textContent = v.color || ('Variant #' + v.variant_id);
        variantSelect.appendChild(opt);
      });
    }
    populateSizes(pid, null);
  };

  const populateSizes = (pid, vid) => {
    clearSelect(sizeSelect, '-- Select Size --');
    const sizes = vid ? (sizesByVariant[vid] || []) : (sizesByProduct[pid] || []);
    sizes.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s;
      opt.textContent = s;
      sizeSelect.appendChild(opt);
    });
  };

  const toggleSizeFields = (pid) => {
    const needSize = requiresSize(pid);
    if (sizeFormGroup) {
      sizeFormGroup.style.display = needSize ? '' : 'none';
    }
    if (sizeSelect) {
      sizeSelect.disabled = !needSize;
      sizeSelect.required = needSize;
    }
    if (newSizeToggle) {
      newSizeToggle.disabled = !needSize;
      if (!needSize) {
        newSizeToggle.checked = false;
      }
    }
    if (sizeNewInput) {
      sizeNewInput.style.display = (needSize && newSizeToggle && newSizeToggle.checked) ? 'block' : 'none';
      sizeNewInput.disabled = !needSize;
      if (!needSize) {
        sizeNewInput.value = '';
      }
    }
    if (!needSize) {
      sizeOption.value = 'existing';
    }
  };

  productSelect.addEventListener('change', () => {
    const pid = productSelect.value;
    toggleSizeFields(pid);
    populateVariants(pid);
  });

  variantSelect.addEventListener('change', () => {
    const pid = productSelect.value;
    const vid = variantSelect.value || null;
    populateSizes(pid, vid);
  });

  newSizeToggle.addEventListener('change', () => {
    const checked = newSizeToggle.checked;
    sizeNewInput.style.display = checked ? 'block' : 'none';
    sizeOption.value = checked ? 'new' : 'existing';
    sizeSelect.disabled = checked;
    if(!checked){
      sizeNewInput.value = '';
    }
  });

  form.addEventListener('submit', (e) => {
    const pid = productSelect.value;
    const needSize = requiresSize(pid);
    if (!needSize) {
      return; // size optional
    }
    const usingNew = newSizeToggle && newSizeToggle.checked;
    const sizeVal = usingNew ? (sizeNewInput.value || '').trim() : (sizeSelect.value || '').trim();
    if (!sizeVal) {
      e.preventDefault();
      alert('Size is required for this product.');
    }
  });

  // initial state
  toggleSizeFields(productSelect.value);
})();
