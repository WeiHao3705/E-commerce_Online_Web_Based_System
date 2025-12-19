(() => {
    const root = document.getElementById('productDetailRoot');
    if (!root) return;

    const variantSizes = JSON.parse(root.dataset.variantSizes || '{}');
    const loginUrl = root.dataset.loginUrl || '../../account.php';
    const userId = (document.body.dataset.userId || '').trim();
    const mainImage = document.getElementById('mainImage');
    const selectedVariantInput = document.getElementById('selectedVariantId');
    const sizeSelect = document.getElementById('sizeSelect');
    const thumbImages = Array.from(document.querySelectorAll('.thumb-image'));
    const addToCartForm = document.querySelector('form.options-section');

    // Modal elements
    const loginModal = document.getElementById('loginModal');
    const loginModalBackdrop = document.getElementById('loginModalBackdrop');
    const loginModalCancel = document.getElementById('loginModalCancel');
    const loginModalLogin = document.getElementById('loginModalLogin');

    function updateSizeOptions() {
        if (!sizeSelect || !selectedVariantInput) return;

        const selectedVid = parseInt(selectedVariantInput.value, 10);
        sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';

        if (Number.isNaN(selectedVid)) return;

        const sizes = variantSizes[selectedVid] || [];
        sizes.forEach((size) => {
            const opt = document.createElement('option');
            opt.value = size;
            opt.textContent = size;
            sizeSelect.appendChild(opt);
        });

        if (sizes.length > 0) {
            sizeSelect.value = sizes[0];
        }
    }

    function openLoginModal() {
        if (!loginModal) return;
        loginModal.classList.add('open');
        loginModal.setAttribute('aria-hidden', 'false');
    }

    function closeLoginModal() {
        if (!loginModal) return;
        loginModal.classList.remove('open');
        loginModal.setAttribute('aria-hidden', 'true');
    }

    function handleLoginRedirect() {
        window.location.href = loginUrl;
    }

    function changeVariant(el) {
        const imagePath = (el.dataset.imagePath || '').trim();
        const vid = el.dataset.variantId;

        if (imagePath && mainImage) {
            const newSrc = '/' + imagePath.replace(/^\/+/u, '');
            mainImage.src = newSrc;
        }

        thumbImages.forEach((t) => t.classList.remove('selected'));
        el.classList.add('selected');

        if (vid && selectedVariantInput) {
            selectedVariantInput.value = vid;
            updateSizeOptions();
        }
    }

    function init() {
        thumbImages.forEach((img) => {
            img.addEventListener('click', () => changeVariant(img));
        });

        updateSizeOptions();

        if (addToCartForm) {
            addToCartForm.addEventListener('submit', (e) => {
                if (!userId) {
                    e.preventDefault();
                    openLoginModal();
                }
            });
        }

        if (loginModalBackdrop) {
            loginModalBackdrop.addEventListener('click', closeLoginModal);
        }

        if (loginModalCancel) {
            loginModalCancel.addEventListener('click', closeLoginModal);
        }

        if (loginModalLogin) {
            loginModalLogin.addEventListener('click', handleLoginRedirect);
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();
