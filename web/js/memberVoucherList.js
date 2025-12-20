function copyVoucherCode(code) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).then(function() {
            alert('Voucher code copied: ' + code);
        }, function() {
            // Fallback for older browsers
            fallbackCopyToClipboard(code);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(code);
    }
}

function fallbackCopyToClipboard(code) {
    const $textarea = $('<textarea>').val(code).css({
        position: 'fixed',
        left: '-9999px',
        top: '-9999px'
    });
    $('body').append($textarea);
    // Use native select() method for textarea (required for clipboard)
    $textarea[0].select();
    try {
        document.execCommand('copy');
        alert('Voucher code copied: ' + code);
    } catch (err) {
        alert('Failed to copy voucher code. Please copy manually: ' + code);
    }
    $textarea.remove();
}

$(document).ready(function() {
    const $sortButton = $('#sortButton');
    const $sortDropdown = $('#sortDropdown');

    if ($sortButton.length && $sortDropdown.length) {
        $sortButton.on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            $sortDropdown.toggleClass('show');
        });

        // Prevent dropdown from closing when clicking inside it
        $sortDropdown.on('click', function(e) {
            e.stopPropagation();
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$sortButton.is(e.target) && 
                !$sortDropdown.is(e.target) && 
                !$sortButton.find(e.target).length && 
                !$sortDropdown.find(e.target).length) {
                $sortDropdown.removeClass('show');
            }
        });
    }

    // QR options dropdown
    const $qrOptionsBtn = $('#btnQrOptions');
    const $qrOptionsMenu = $('#qrOptionsMenu');

    if ($qrOptionsBtn.length && $qrOptionsMenu.length) {
        $qrOptionsBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $qrOptionsMenu.toggleClass('show');
        });

        // Close when clicking outside
        $(document).on('click', function(e) {
            if (
                !$qrOptionsBtn.is(e.target) &&
                !$qrOptionsMenu.is(e.target) &&
                !$qrOptionsBtn.find(e.target).length &&
                !$qrOptionsMenu.find(e.target).length
            ) {
                $qrOptionsMenu.removeClass('show');
            }
        });
    }

    // Shop Now button handler
    $(document).on('click', '.btn-shop-now', function() {
        const shopUrl = $(this).data('shop-url');
        if (shopUrl) {
            window.location.href = shopUrl;
        }
    });

    // Copy code button handler
    $(document).on('click', '.btn-copy-code', function() {
        const code = $(this).data('voucher-code');
        if (code) {
            copyVoucherCode(code);
        }
    });

    // --- QR scan via camera / image upload ---
    let html5QrCode = null;
    const $qrModal = $('#qrScanModal');
    const $voucherInput = $('#voucher_code');
    const $qrImageInput = $('#qrImageInput');
    const $redeemModal = $('#redeemConfirmModal');
    const $redeemCodeLabel = $('#redeemVoucherCodeLabel');
    let redeemConfirmed = false;

    // Intercept redeem form submit to show custom confirmation UI
    $('#redeemVoucherForm').on('submit', function(e) {
        if (redeemConfirmed) {
            // Allow the submission to proceed once user has confirmed
            redeemConfirmed = false;
            return;
        }

        e.preventDefault();

        const code = ($voucherInput.val() || '').trim();
        $redeemCodeLabel.text(code || '(no code entered)');

        $redeemModal.removeClass('hidden');
    });

    $('#btnRedeemCancel, #btnCloseRedeemModal').on('click', function() {
        $redeemModal.addClass('hidden');
    });

    // Clicking the backdrop should also close the confirm modal (but not the QR camera)
    $(document).on('click', '#redeemConfirmModal .qr-scan-backdrop', function() {
        $redeemModal.addClass('hidden');
    });

    $('#btnRedeemConfirm').on('click', function() {
        redeemConfirmed = true;
        $redeemModal.addClass('hidden');
        $('#redeemVoucherForm').trigger('submit');
    });

    function handleQrResult(decodedText) {
        if (!decodedText) {
            return;
        }
        $voucherInput.val(decodedText.trim());

        // Auto-submit the redeem form once a code is scanned
        const $form = $('#redeemVoucherForm');
        if ($form.length) {
            $form.trigger('submit');
        }

        // Close camera if open
        stopCameraScan();
    }

    function startCameraScan() {
        if (typeof Html5Qrcode === 'undefined') {
            alert('QR scanner library failed to load. Please try again or enter the code manually.');
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qr-reader");
        }

        $qrModal.removeClass('hidden');

        html5QrCode.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: 250
            },
            function(decodedText, decodedResult) {
                handleQrResult(decodedText);
            },
            function(errorMessage) {
                // Ignore scan errors / no QR in frame
            }
        ).catch(function(err) {
            alert('Unable to access camera: ' + err);
            $qrModal.addClass('hidden');
        });
    }

    function stopCameraScan() {
        if (html5QrCode && html5QrCode._isScanning) {
            html5QrCode.stop().then(function() {
                $qrModal.addClass('hidden');
            }).catch(function() {
                $qrModal.addClass('hidden');
            });
        } else {
            $qrModal.addClass('hidden');
        }
    }

    $('#btnScanQrCamera').on('click', function() {
        startCameraScan();
    });

    $('#btnCloseQrModal').on('click', function() {
        stopCameraScan();
    });

    // Close modal when clicking on backdrop
    $(document).on('click', '.qr-scan-backdrop', function() {
        stopCameraScan();
    });

    // Image upload QR scan
    $('#btnScanQrImage').on('click', function() {
        if (typeof Html5Qrcode === 'undefined') {
            alert('QR scanner library failed to load. Please try again or enter the code manually.');
            return;
        }
        $qrImageInput.val('');
        $qrImageInput.trigger('click');
    });

    $qrImageInput.on('change', function(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) {
            return;
        }

        const tempReaderId = "qr-reader-file-temp";
        let $tempDiv = $('#' + tempReaderId);
        if ($tempDiv.length === 0) {
            $tempDiv = $('<div>')
                .attr('id', tempReaderId)
                .css({ width: 0, height: 0, overflow: 'hidden' });
            $('body').append($tempDiv);
        }

        const fileScanner = new Html5Qrcode(tempReaderId);
        fileScanner.scanFile(file, true)
            .then(function(decodedText) {
                handleQrResult(decodedText);
                fileScanner.clear();
            })
            .catch(function(err) {
                alert('Could not read QR code from image. Please try another image or type the code manually.');
                fileScanner.clear();
            });
    });
});

