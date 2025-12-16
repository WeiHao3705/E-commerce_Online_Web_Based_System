$(document).ready(function() {
    let cropper;
    let currentFile;

    // Get data attributes from body
    const userId = document.body.dataset.userId;
    const controllerUrl = document.body.dataset.controllerUrl;

    // Open update modal
    $('#btnOpenUpdate').on('click', function() {
        $('#updateModal').addClass('open');
    });

    // Close update modal
    $('#btnCloseUpdate, #btnCancelUpdate').on('click', function() {
        $('#updateModal').removeClass('open');
    });

    // Trigger file input on avatar badge click
    $('#uploadPhotoBtn').on('click', function() {
        $('#photoUpload').click();
    });

    // Handle photo upload
    $('#photoUpload').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            currentFile = file;
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#cropperImage').attr('src', event.target.result);
                $('#photoCropperModal').addClass('open');
                
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(document.getElementById('cropperImage'), {
                    aspectRatio: 1,
                    viewMode: 2,
                    autoCropArea: 1,
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // Cropper controls
    $('#btnRotateLeft').on('click', () => cropper.rotate(-45));
    $('#btnRotateRight').on('click', () => cropper.rotate(45));
    $('#btnZoomIn').on('click', () => cropper.zoom(0.1));
    $('#btnZoomOut').on('click', () => cropper.zoom(-0.1));
    
    let scaleX = 1, scaleY = 1;
    $('#btnFlipHorizontal').on('click', function() {
        scaleX = -scaleX;
        cropper.scaleX(scaleX);
    });
    $('#btnFlipVertical').on('click', function() {
        scaleY = -scaleY;
        cropper.scaleY(scaleY);
    });

    // Close cropper modal
    $('#btnCloseCropper, #btnCancelCrop').on('click', function() {
        $('#photoCropperModal').removeClass('open');
        if (cropper) {
            cropper.destroy();
        }
        $('#photoUpload').val('');
    });

    // Save cropped photo
    $('#btnSaveCrop').on('click', function() {
        if (!cropper) return;
        
        cropper.getCroppedCanvas({
            width: 400,
            height: 400,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        }).toBlob(function(blob) {
            const formData = new FormData();
            formData.append('action', 'update_photo');
            formData.append('user_id', userId);
            formData.append('photo', blob, currentFile.name);

            $.ajax({
                url: controllerUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        $('#profilePhoto').attr('src', result.photoUrl + '?t=' + new Date().getTime());
                        $('#photoCropperModal').removeClass('open');
                        if (cropper) cropper.destroy();
                        alert('Profile photo updated successfully!');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to upload photo'));
                    }
                },
                error: function() {
                    alert('Error uploading photo. Please try again.');
                }
            });
        }, 'image/jpeg', 0.95);
    });

    // Close modal on overlay click
    $('.modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('open');
            if (cropper) cropper.destroy();
        }
    });

    // Form validation
    $('#updateForm').on('submit', function(e) {
        let isValid = true;
        $('.form-error').hide();

        const fullName = $('#full_name').val().trim();
        if (fullName === '') {
            $('#err_full_name').show();
            isValid = false;
        }

        const email = $('#email').val().trim();
        if (email === '' || !email.includes('@')) {
            $('#err_email').show();
            isValid = false;
        }

        const contactNo = $('#contact_no').val().trim();
        const phonePattern = /^[0-9]{3}-[0-9]{3}-[0-9]{4}$/;
        if (!phonePattern.test(contactNo)) {
            $('#err_contact_no').show();
            isValid = false;
        }

        // Password validation (only if user is trying to change password)
        const currentPassword = $('#current_password').val().trim();
        const newPassword = $('#new_password').val().trim();
        const confirmPassword = $('#confirm_password').val().trim();

        // If any password field is filled, validate all password fields
        if (currentPassword !== '' || newPassword !== '' || confirmPassword !== '') {
            if (currentPassword === '') {
                $('#err_current_password').show();
                isValid = false;
            }

            if (newPassword === '') {
                $('#err_new_password').text('New password is required.');
                $('#err_new_password').show();
                isValid = false;
            } else if (newPassword.length < 8) {
                $('#err_new_password').text('Password must be at least 8 characters.');
                $('#err_new_password').show();
                isValid = false;
            }

            if (confirmPassword === '') {
                $('#err_confirm_password').text('Please confirm your new password.');
                $('#err_confirm_password').show();
                isValid = false;
            } else if (newPassword !== confirmPassword) {
                $('#err_confirm_password').text('Passwords do not match.');
                $('#err_confirm_password').show();
                isValid = false;
            }
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Auto-format phone number
    $('#contact_no').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 6) {
            value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
        } else if (value.length >= 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        $(this).val(value);
    });
});
