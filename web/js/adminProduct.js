(function () {
    var modal = document.getElementById('createModal');
    var openBtn = document.getElementById('openCreateModal');
    var closeBtn = document.getElementById('closeCreateModal');
    var cancelBtn = document.getElementById('cancelCreate');

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    if (openBtn && modal) {
        openBtn.addEventListener('click', openModal);
    }

    [closeBtn, cancelBtn].forEach(function (btn) {
        if (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                closeModal();
            });
        }
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    var deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var ok = window.confirm('Delete this product? This cannot be undone.');
            if (!ok) {
                event.preventDefault();
            }
        });
    });
})();
