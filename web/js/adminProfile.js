$(document).ready(function() {
    let cropper;
    let currentFile;
    let cameraStream = null;

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

    // ===== Photo Upload Modal =====
    
    // Open Photo Upload Modal (instead of directly opening file picker)
    $('#uploadPhotoBtn').on('click', function() {
        // Update current photo preview
        const profilePhotoSrc = $('#profilePhoto').attr('src');
        $('#currentPhotoPreview').attr('src', profilePhotoSrc);
        $('#photoUploadModal').addClass('open');
    });

    // Close Photo Upload Modal
    $('#btnClosePhotoUpload').on('click', function() {
        closePhotoUploadModal();
    });

    function closePhotoUploadModal() {
        $('#photoUploadModal').removeClass('open');
        stopCamera();
    }

    // Browse Files Button - use event delegation
    $(document).on('click', '#btnSelectFile', function(e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('photoFileInput').click();
    });

    // File Input Change
    $(document).on('change', '#photoFileInput', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleImageFile(file);
        }
    });

    // Drag and Drop
    const uploadDropZone = document.getElementById('uploadDropZone');
    if (uploadDropZone) {
        uploadDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });

        uploadDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        uploadDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                handleImageFile(files[0]);
            }
        });

        // Click on drop zone (excluding buttons)
        $(uploadDropZone).on('click', function(e) {
            if ($(e.target).closest('.upload-btn').length === 0) {
                document.getElementById('photoFileInput').click();
            }
        });
    }

    // Camera functionality - use event delegation
    $(document).on('click', '#btnOpenCamera', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            startCamera();
        } else {
            // Fallback to file input with capture attribute (mobile)
            document.getElementById('cameraInput').click();
        }
    });

    // Camera input change (mobile fallback)
    $(document).on('change', '#cameraInput', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleImageFile(file);
        }
    });

    function startCamera() {
        $('#uploadDropZone').hide();
        $('#cameraSection').show();
        
        navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        })
        .then(function(stream) {
            cameraStream = stream;
            document.getElementById('cameraVideo').srcObject = stream;
        })
        .catch(function(err) {
            console.error('Camera error:', err);
            alert('Unable to access camera. Please check your permissions.');
            stopCamera();
        });
    }

    function stopCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(function(track) {
                track.stop();
            });
            cameraStream = null;
        }
        const video = document.getElementById('cameraVideo');
        if (video) video.srcObject = null;
        $('#cameraSection').hide();
        $('#uploadDropZone').show();
    }

    // Capture photo from camera
    $('#btnCapture').on('click', function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            canvas.toBlob(function(blob) {
                stopCamera();
                const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
                handleImageFile(file);
            }, 'image/jpeg', 0.9);
        }
    });

    // Cancel camera
    $('#btnCancelCamera').on('click', stopCamera);

    // Handle image file - open cropper
    function handleImageFile(file) {
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }

        currentFile = file;
        const reader = new FileReader();
        reader.onload = function(event) {
            // Close upload modal
            closePhotoUploadModal();
            
            // Open cropper modal
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

    // Legacy file input handler (backward compatibility)
    $('#photoUpload').on('change', function(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            handleImageFile(file);
        }
    });

    // Cropper controls
    $('#btnRotateLeft').on('click', () => cropper && cropper.rotate(-45));
    $('#btnRotateRight').on('click', () => cropper && cropper.rotate(45));
    $('#btnZoomIn').on('click', () => cropper && cropper.zoom(0.1));
    $('#btnZoomOut').on('click', () => cropper && cropper.zoom(-0.1));
    
    let scaleX = 1, scaleY = 1;
    $('#btnFlipHorizontal').on('click', function() {
        if (cropper) {
            scaleX = -scaleX;
            cropper.scaleX(scaleX);
        }
    });
    $('#btnFlipVertical').on('click', function() {
        if (cropper) {
            scaleY = -scaleY;
            cropper.scaleY(scaleY);
        }
    });

    // Close cropper modal
    $('#btnCloseCropper, #btnCancelCrop').on('click', function() {
        $('#photoCropperModal').removeClass('open');
        if (cropper) {
            cropper.destroy();
        }
        $('#photoUpload').val('');
        $('#photoFileInput').val('');
        $('#cameraInput').val('');
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
            formData.append('photo', blob, currentFile ? currentFile.name : 'profile.jpg');

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
                        
                        // Show success message
                        const alertDiv = $('<div class="alert alert-success">Profile photo updated successfully!</div>');
                        $('.admin-profile-wrapper').prepend(alertDiv);
                        setTimeout(function() { alertDiv.remove(); }, 3000);
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
            stopCamera();
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
        const digits = contactNo.replace(/\D+/g, '');
        // Validate Malaysian phone number (10-11 digits)
        if (digits.length < 10 || digits.length > 11) {
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

    // Auto-format Malaysian phone number
    $('#contact_no').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        // Format Malaysian phone number (10-11 digits)
        if (value.length >= 11) {
            value = value.substring(0, 3) + '-' + value.substring(3, 7) + ' ' + value.substring(7, 11);
        } else if (value.length >= 7) {
            value = value.substring(0, 3) + '-' + value.substring(3, 6) + ' ' + value.substring(6);
        } else if (value.length >= 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        $(this).val(value);
    });
});
