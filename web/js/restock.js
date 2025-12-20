(function(){
  const form = document.getElementById('restockForm');
  if(!form){return;}
  const productSelect = document.getElementById('productSelect');
  const variantSelect = document.getElementById('variantSelect');
  const sizeSelect = document.getElementById('sizeSelect');
  const newSizeToggle = document.getElementById('newSizeToggle');
  const sizeNewInput = document.getElementById('sizeNew');
  const sizeOption = document.getElementById('sizeOption');

  const variantsMap = JSON.parse(form.dataset.variants || '{}');
  const sizesByProduct = JSON.parse(form.dataset.sizesProduct || '{}');
  const sizesByVariant = JSON.parse(form.dataset.sizesVariant || '{}');

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

  productSelect.addEventListener('change', () => {
    const pid = productSelect.value;
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
})();
