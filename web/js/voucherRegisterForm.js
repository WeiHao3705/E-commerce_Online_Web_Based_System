$(document).ready(function() {
    // Bulk Import Modal
    const $bulkImportBtn = $('#bulk-import-btn');
    const $bulkImportModal = $('#bulk-import-modal');
    const $closeModal = $('#close-modal');
    const $cancelImport = $('#cancel-import');

    $bulkImportBtn.on('click', function() {
        $bulkImportModal.removeClass('hidden');
    });

    $closeModal.on('click', function() {
        $bulkImportModal.addClass('hidden');
    });

    $cancelImport.on('click', function() {
        $bulkImportModal.addClass('hidden');
    });

    $bulkImportModal.on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
            $bulkImportModal.addClass('hidden');
        }
    });

    // Generate random voucher code
    $('#generate-code').on('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#code').val(code);
    });

    // Update discount prefix and show/hide max discount based on type
    $('#type').on('change', function() {
        const type = $(this).val();
        const $discountPrefix = $('#discount-prefix');
        const $discountValueInput = $('#discount-value');
        const $maxDiscountGroup = $('#max-discount-group');

        if (type === 'percent') {
            $discountPrefix.text('%');
            $discountValueInput.attr('max', '100');
            $maxDiscountGroup.show();
        } else if (type === 'fixed') {
            $discountPrefix.text('$');
            $discountValueInput.removeAttr('max');
            $maxDiscountGroup.hide();
        } else if (type === 'freeshipping') {
            $discountPrefix.text('');
            $discountValueInput.val('0');
            $discountValueInput.attr('readonly', 'readonly');
            $maxDiscountGroup.hide();
        } else {
            $discountValueInput.removeAttr('readonly');
            $maxDiscountGroup.hide();
        }
    });

    // Validate end date is after start date
    $('#start-date').on('change', function() {
        const startDate = new Date($(this).val());
        const $endDateInput = $('#end-date');
        const endDate = new Date($endDateInput.val());

        if ($endDateInput.val() && endDate < startDate) {
            alert('End date must be after start date!');
            $endDateInput.val('');
        }
    });

    $('#end-date').on('change', function() {
        const $startDateInput = $('#start-date');
        const startDate = new Date($startDateInput.val());
        const endDate = new Date($(this).val());

        if ($startDateInput.val() && endDate < startDate) {
            alert('End date must be after start date!');
            $(this).val('');
        }
    });

    // Validate discount value based on type
    $('#discount-value').on('input', function() {
        const type = $('#type').val();
        const value = parseFloat($(this).val());

        if (type === 'percent' && (value < 0 || value > 100)) {
            $(this).css('border-color', '#ef4444');
        } else {
            $(this).css('border-color', '');
        }
    });

    // Toggle switch functionality
    const $statusToggle = $('#status-toggle');
    $statusToggle.on('click', function() {
        const isChecked = $(this).attr('aria-checked') === 'true';
        $(this).attr('aria-checked', isChecked ? 'false' : 'true');
    });

    // Trigger type change on page load if type is already selected
    if ($('#type').val()) {
        $('#type').trigger('change');
    }

    // Scroll to error field if exists
    const errorFieldId = $('#error-field-id').data('field-id');
    if (errorFieldId) {
        const $errorField = $('#' + errorFieldId);
        if ($errorField.length) {
            $errorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $errorField.focus();
        }
    }
});

