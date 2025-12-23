(function(){
  // Get data attributes from body
  var userId = document.body.dataset.userId;
  var controllerUrl = document.body.dataset.controllerUrl;
  var prefix = document.body.dataset.prefix;

  // Photo Upload Modal Elements
  var uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
  var photoUploadModal = document.getElementById('photoUploadModal');
  var btnClosePhotoUpload = document.getElementById('btnClosePhotoUpload');
  var uploadDropZone = document.getElementById('uploadDropZone');
  var btnSelectFile = document.getElementById('btnSelectFile');
  var btnOpenCamera = document.getElementById('btnOpenCamera');
  var photoFileInput = document.getElementById('photoFileInput');
  var cameraInput = document.getElementById('cameraInput');
  var currentPhotoPreview = document.getElementById('currentPhotoPreview');
  
  // Camera Section Elements
  var cameraSection = document.getElementById('cameraSection');
  var cameraVideo = document.getElementById('cameraVideo');
  var cameraCanvas = document.getElementById('cameraCanvas');
  var btnCapture = document.getElementById('btnCapture');
  var btnCancelCamera = document.getElementById('btnCancelCamera');
  var cameraStream = null;

  // Photo Cropper Elements
  var photoUpload = document.getElementById('photoUpload'); // legacy hidden input
  var photoCropperModal = document.getElementById('photoCropperModal');
  var cropperImage = document.getElementById('cropperImage');
  var btnCancelCrop = document.getElementById('btnCancelCrop');
  var btnSaveCrop = document.getElementById('btnSaveCrop');
  var cropper = null;
  var scaleX = 1;
  var scaleY = 1;

  // Open Photo Upload Modal
  if(uploadPhotoBtn) {
    uploadPhotoBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openPhotoUploadModal();
    });
  }

  function openPhotoUploadModal() {
    // Update current photo preview
    var profilePhoto = document.getElementById('profilePhoto');
    if(currentPhotoPreview && profilePhoto) {
      currentPhotoPreview.src = profilePhoto.src;
    }
    photoUploadModal.classList.add('open');
    photoUploadModal.setAttribute('aria-hidden', 'false');
  }

  function closePhotoUploadModal() {
    photoUploadModal.classList.remove('open');
    photoUploadModal.setAttribute('aria-hidden', 'true');
    stopCamera();
  }

  // Close Photo Upload Modal
  if(btnClosePhotoUpload) {
    btnClosePhotoUpload.addEventListener('click', closePhotoUploadModal);
  }

  // Close modal on overlay click
  if(photoUploadModal) {
    photoUploadModal.addEventListener('click', function(e) {
      if(e.target === photoUploadModal) {
        closePhotoUploadModal();
      }
    });
  }

  // Browse Files Button
  if(btnSelectFile) {
    btnSelectFile.addEventListener('click', function(e) {
      e.stopPropagation();
      photoFileInput.click();
    });
  }

  // File Input Change
  if(photoFileInput) {
    photoFileInput.addEventListener('change', function(e) {
      var file = e.target.files[0];
      if(file) {
        handleImageFile(file);
      }
    });
  }

  // Drag and Drop
  if(uploadDropZone) {
    uploadDropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.stopPropagation();
      uploadDropZone.classList.add('drag-over');
    });

    uploadDropZone.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      uploadDropZone.classList.remove('drag-over');
    });

    uploadDropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      uploadDropZone.classList.remove('drag-over');
      
      var files = e.dataTransfer.files;
      if(files.length > 0 && files[0].type.startsWith('image/')) {
        handleImageFile(files[0]);
      }
    });

    // Click on drop zone (excluding buttons)
    uploadDropZone.addEventListener('click', function(e) {
      if(e.target === uploadDropZone || e.target.closest('.upload-icon') || e.target.closest('.upload-text') || e.target.closest('.upload-subtext')) {
        photoFileInput.click();
      }
    });
  }

  // Camera functionality
  if(btnOpenCamera) {
    btnOpenCamera.addEventListener('click', function(e) {
      e.stopPropagation();
      // Check if device supports getUserMedia
      if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        startCamera();
      } else {
        // Fallback to file input with capture attribute (mobile)
        cameraInput.click();
      }
    });
  }

  // Camera input change (mobile fallback)
  if(cameraInput) {
    cameraInput.addEventListener('change', function(e) {
      var file = e.target.files[0];
      if(file) {
        handleImageFile(file);
      }
    });
  }

  function startCamera() {
    uploadDropZone.style.display = 'none';
    cameraSection.style.display = 'block';
    
    navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: 'user',
        width: { ideal: 640 },
        height: { ideal: 480 }
      } 
    })
    .then(function(stream) {
      cameraStream = stream;
      cameraVideo.srcObject = stream;
    })
    .catch(function(err) {
      console.error('Camera error:', err);
      alert('Unable to access camera. Please check your permissions.');
      stopCamera();
    });
  }

  function stopCamera() {
    if(cameraStream) {
      cameraStream.getTracks().forEach(function(track) {
        track.stop();
      });
      cameraStream = null;
    }
    cameraVideo.srcObject = null;
    cameraSection.style.display = 'none';
    uploadDropZone.style.display = 'block';
  }

  // Capture photo from camera
  if(btnCapture) {
    btnCapture.addEventListener('click', function() {
      if(cameraVideo.readyState === cameraVideo.HAVE_ENOUGH_DATA) {
        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;
        var ctx = cameraCanvas.getContext('2d');
        ctx.drawImage(cameraVideo, 0, 0);
        
        cameraCanvas.toBlob(function(blob) {
          stopCamera();
          var file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
          handleImageFile(file);
        }, 'image/jpeg', 0.9);
      }
    });
  }

  // Cancel camera
  if(btnCancelCamera) {
    btnCancelCamera.addEventListener('click', stopCamera);
  }

  // Handle image file - open cropper
  function handleImageFile(file) {
    if(!file.type.startsWith('image/')) {
      alert('Please select an image file.');
      return;
    }
    
    if(file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB.');
      return;
    }

    var reader = new FileReader();
    reader.onload = function(event) {
      // Close upload modal
      closePhotoUploadModal();
      
      // Open cropper modal
      cropperImage.src = event.target.result;
      photoCropperModal.classList.add('open');
      photoCropperModal.setAttribute('aria-hidden', 'false');
      
      // Initialize cropper
      if(cropper) {
        cropper.destroy();
      }
      cropper = new Cropper(cropperImage, {
        aspectRatio: 1,
        viewMode: 2,
        dragMode: 'move',
        autoCropArea: 1,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
      });
      scaleX = 1;
      scaleY = 1;
    };
    reader.readAsDataURL(file);
  }

  // Legacy file input handler (for backward compatibility)
  if(photoUpload) {
    photoUpload.addEventListener('change', function(e) {
      var file = e.target.files[0];
      if(file && file.type.startsWith('image/')) {
        handleImageFile(file);
      }
    });
  }

  // Cropper controls
  document.getElementById('zoomIn')?.addEventListener('click', function() {
    if(cropper) cropper.zoom(0.1);
  });
  document.getElementById('zoomOut')?.addEventListener('click', function() {
    if(cropper) cropper.zoom(-0.1);
  });
  document.getElementById('rotateLeft')?.addEventListener('click', function() {
    if(cropper) cropper.rotate(-45);
  });
  document.getElementById('rotateRight')?.addEventListener('click', function() {
    if(cropper) cropper.rotate(45);
  });
  document.getElementById('flipH')?.addEventListener('click', function() {
    if(cropper) { scaleX = -scaleX; cropper.scaleX(scaleX); }
  });
  document.getElementById('flipV')?.addEventListener('click', function() {
    if(cropper) { scaleY = -scaleY; cropper.scaleY(scaleY); }
  });

  // Cancel crop
  if(btnCancelCrop) {
    btnCancelCrop.addEventListener('click', function() {
      photoCropperModal.classList.remove('open');
      photoCropperModal.setAttribute('aria-hidden', 'true');
      if(cropper) {
        cropper.destroy();
        cropper = null;
      }
      photoFileInput.value = '';
      cameraInput.value = '';
    });
  }

  // Save cropped image
  if(btnSaveCrop) {
    btnSaveCrop.addEventListener('click', function() {
      if(cropper) {
        var canvas = cropper.getCroppedCanvas({
          width: 400,
          height: 400,
          imageSmoothingEnabled: true,
          imageSmoothingQuality: 'high',
        });
        
        canvas.toBlob(function(blob) {
          var formData = new FormData();
          formData.append('photo', blob, 'profile.jpg');
          formData.append('action', 'update_photo');
          formData.append('user_id', userId);
          
          // Upload the photo
          fetch(controllerUrl, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if(data.success) {
              // Update the profile photo with cache busting
              var profilePhoto = document.getElementById('profilePhoto');
              profilePhoto.src = data.photoUrl + '?t=' + new Date().getTime();
              
              // Close modal
              photoCropperModal.classList.remove('open');
              photoCropperModal.setAttribute('aria-hidden', 'true');
              
              // Show success message
              var successDiv = document.createElement('div');
              successDiv.className = 'alert alert-success';
              successDiv.textContent = 'Profile photo updated successfully!';
              document.querySelector('.profile-page').insertBefore(successDiv, document.querySelector('.breadcrumb'));
              setTimeout(function() { successDiv.remove(); }, 3000);
              
              if(cropper) {
                cropper.destroy();
                cropper = null;
              }
              photoFileInput.value = '';
              cameraInput.value = '';
            } else {
              alert('Error: ' + (data.message || 'Failed to upload photo'));
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading the photo.');
          });
        }, 'image/jpeg', 0.9);
      }
    });
  }

  // Profile update modal
  var openBtn = document.getElementById('btnOpenUpdate');
  var modal = document.getElementById('updateModal');
  var cancelBtn = document.getElementById('btnCancelUpdate');
  var closeBtn = document.getElementById('btnCloseUpdate');
  var saveBtn = document.getElementById('btnSaveUpdate');
  var contact = document.getElementById('contact_no');

  // Add Address modal
  var addAddressModal = document.getElementById('addAddressModal');
  var btnOpenAddAddress = document.getElementById('btnOpenAddAddress');
  var btnCancelAddAddress = document.getElementById('btnCancelAddAddress');
  var btnCloseAddAddress = document.getElementById('btnCloseAddAddress');
  var addAddressForm = document.getElementById('addAddressForm');

  // Photo Cropper modal
  var btnClosePhotoCropper = document.getElementById('btnClosePhotoCropper');

  // Simple input masking: auto-insert dashes and space for 000-000 0000
  if(contact){
    contact.addEventListener('input', function(){
      var d = contact.value.replace(/\D+/g,'').slice(0,10);
      var out = d;
      if(d.length >= 4 && d.length <= 6){ out = d.slice(0,3) + '-' + d.slice(3); }
      else if(d.length >= 7){ out = d.slice(0,3) + '-' + d.slice(3,6) + ' ' + d.slice(6); }
      contact.value = out;
    });
  }

  // Update selected address ID before form submission
  document.getElementById('updateForm')?.addEventListener('submit', function(e) {
    var selectedRadio = document.querySelector('input[name="address_selection"]:checked');
    if(selectedRadio) {
      document.getElementById('selectedAddressId').value = selectedRadio.value;
    }
  });

  // Client-side validation for password update (optional)
  document.getElementById('updateForm')?.addEventListener('submit', function(e) {
    // hide previous errors (if any)
    try { document.querySelectorAll('.form-error').forEach(el => el.style.display = 'none'); } catch(err){}

    var currentPassword = document.getElementById('current_password')?.value.trim() || '';
    var newPassword = document.getElementById('new_password')?.value.trim() || '';
    var confirmPassword = document.getElementById('confirm_password')?.value.trim() || '';

    // If any password field is filled, validate all
    if (currentPassword !== '' || newPassword !== '' || confirmPassword !== '') {
      var ok = true;
      if (currentPassword === '') { document.getElementById('err_current_password').style.display = 'block'; ok = false; }
      if (newPassword === '') { document.getElementById('err_new_password').textContent = 'New password is required.'; document.getElementById('err_new_password').style.display = 'block'; ok = false; }
      else if (newPassword.length < 8) { document.getElementById('err_new_password').textContent = 'Password must be at least 8 characters.'; document.getElementById('err_new_password').style.display = 'block'; ok = false; }
      if (confirmPassword === '') { document.getElementById('err_confirm_password').textContent = 'Please confirm your new password.'; document.getElementById('err_confirm_password').style.display = 'block'; ok = false; }
      else if (newPassword !== confirmPassword) { document.getElementById('err_confirm_password').textContent = 'Passwords do not match.'; document.getElementById('err_confirm_password').style.display = 'block'; ok = false; }

      if (!ok) {
        e.preventDefault();
        // scroll to first visible error
        var firstErr = document.querySelector('.form-error[style*="display: block"]');
        if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'});
      }
    }
  });

  // Label buttons for address
  document.querySelectorAll('.label-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      document.getElementById('address_label').value = this.getAttribute('data-label');
    });
  });

  // Open Add Address modal
  if(btnOpenAddAddress) {
    btnOpenAddAddress.addEventListener('click', function(e) {
      e.preventDefault();
      // Reset form for adding new address
      addAddressForm.reset();
      document.getElementById('address_id').value = '';
      document.getElementById('addressFormAction').value = 'add_address';
      document.getElementById('addressModalTitle').textContent = 'Add New Address';
      document.getElementById('addressSubmitText').textContent = 'Add Address';
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      document.querySelector('.label-btn[data-label="home"]').classList.add('active');
      document.getElementById('address_label').value = 'home';
      
      // Hide map and reset location status when opening modal
      var mapWrapperEl = document.getElementById('mapWrapper');
      var locationStatusEl = document.getElementById('locationStatus');
      if(mapWrapperEl) mapWrapperEl.style.display = 'none';
      if(locationStatusEl) locationStatusEl.style.display = 'none';
      
      addAddressModal.classList.add('open');
      addAddressModal.setAttribute('aria-hidden', 'false');
    });
  }

  // Close Add Address modal
  if(btnCancelAddAddress) {
    btnCancelAddAddress.addEventListener('click', function(e) {
      e.preventDefault();
      addAddressModal.classList.remove('open');
      addAddressModal.setAttribute('aria-hidden', 'true');
      addAddressForm.reset();
      document.getElementById('address_id').value = '';
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      document.querySelector('.label-btn[data-label="home"]').classList.add('active');
      // Hide map when closing modal
      var mapWrapperEl = document.getElementById('mapWrapper');
      var locationStatusEl = document.getElementById('locationStatus');
      if(mapWrapperEl) mapWrapperEl.style.display = 'none';
      if(locationStatusEl) locationStatusEl.style.display = 'none';
    });
  }

  // Submit Add Address form
  if(addAddressForm) {
    addAddressForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      var formData = new FormData(this);
      
      fetch(controllerUrl, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          // Reload to show new address
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to add address'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the address.');
      });
    });
  }

  // Edit Address functionality - reuse add address modal
  document.addEventListener('click', function(e) {
    if(e.target.closest('.btn-edit-address')) {
      e.preventDefault();
      var btn = e.target.closest('.btn-edit-address');
      var addressData = JSON.parse(btn.getAttribute('data-address'));
      
      // Populate form with existing data
      document.getElementById('address_id').value = addressData.id;
      document.getElementById('addressFormAction').value = 'edit_address';
      document.getElementById('address1').value = addressData.address1;
      document.getElementById('address2').value = addressData.address2 || '';
      document.getElementById('city').value = addressData.city;
      document.getElementById('state').value = addressData.state;
      document.getElementById('postcode').value = addressData.postcode;
      
      // Update modal title and button text
      document.getElementById('addressModalTitle').textContent = 'Edit Address';
      document.getElementById('addressSubmitText').textContent = 'Save Changes';
      
      // Set active label button based on address label
      var labelValue = (addressData.label || 'home').toLowerCase();
      document.querySelectorAll('.label-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if(btn.getAttribute('data-label') === labelValue) {
          btn.classList.add('active');
        }
      });
      document.getElementById('address_label').value = labelValue;
      
      // Open modal
      addAddressModal.classList.add('open');
      addAddressModal.setAttribute('aria-hidden', 'false');
    }
  });

  // Delete Address functionality
  document.addEventListener('click', function(e) {
    if(e.target.closest('.btn-delete-address')) {
      e.preventDefault();
      var btn = e.target.closest('.btn-delete-address');
      var addressId = btn.getAttribute('data-address-id');
      
      if(confirm('Are you sure you want to delete this address?')) {
        var formData = new FormData();
        formData.append('action', 'delete_address');
        formData.append('user_id', userId);
        formData.append('address_id', addressId);
        
        fetch(controllerUrl, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if(data.success) {
            location.reload();
          } else {
            alert('Error: ' + (data.message || 'Failed to delete address'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting the address.');
        });
      }
    }
  });

  function validateForm(){
    var ok = true;
    var full = document.getElementById('full_name');
    var email = document.getElementById('email');
    var contact = document.getElementById('contact_no');
    // Reset
    ['err_full_name','err_email','err_contact'].forEach(function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; });
    // Full name
    if(!full.value.trim()){ document.getElementById('err_full_name').style.display='block'; ok=false; }
    // Email format
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
    if(!emailOk){ document.getElementById('err_email').style.display='block'; ok=false; }
    // Malaysian phone number validation (10-11 digits)
    var phoneDigits = contact.value.replace(/\D/g, '');
    var phoneOk = phoneDigits.length >= 10 && phoneDigits.length <= 11;
    if(!phoneOk){ document.getElementById('err_contact').style.display='block'; ok=false; }
    return ok;
  }

  function openModal(e){ if(e) e.preventDefault(); modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(e){ if(e) e.preventDefault(); modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }

  if(openBtn) openBtn.addEventListener('click', openModal);
  if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
  if(closeBtn) closeBtn.addEventListener('click', closeModal);
  if(btnCloseAddAddress) btnCloseAddAddress.addEventListener('click', function(e) { 
    e.preventDefault(); 
    btnCancelAddAddress.click(); 
  });
  if(btnClosePhotoCropper) btnClosePhotoCropper.addEventListener('click', function(e) {
    e.preventDefault();
    btnCancelCrop.click();
  });
  if(modal) modal.addEventListener('click', function(e){ if(e.target === modal){ closeModal(e); } });
  if(addAddressModal) addAddressModal.addEventListener('click', function(e){ if(e.target === addAddressModal){ btnCancelAddAddress.click(); } });
  document.addEventListener('keydown', function(e){ 
    if(e.key === 'Escape') {
      if(addAddressModal && addAddressModal.classList.contains('open')) { btnCancelAddAddress.click(); }
      else if(modal && modal.classList.contains('open')) { closeModal(e); }
    }
  });
  if(saveBtn){
    saveBtn.addEventListener('click', function(e){
      var ok = validateForm();
      if(!ok){ e.preventDefault(); }
    });
  }

  // ===== Get Current Location (Google Maps Integration) =====
  var btnGetLocation = document.getElementById('btnGetCurrentLocation');
  var locationStatus = document.getElementById('locationStatus');
  var locationSpinner = document.getElementById('locationSpinner');
  var locationBtnText = document.getElementById('locationBtnText');
  var mapWrapper = document.getElementById('mapWrapper');
  var locationMap = null;
  var locationMarker = null;

  // Malaysian state mapping for geocoding results
  var malaysianStates = {
    'johor': 'Johor',
    'kedah': 'Kedah',
    'kelantan': 'Kelantan',
    'kuala lumpur': 'Kuala Lumpur',
    'wilayah persekutuan kuala lumpur': 'Kuala Lumpur',
    'federal territory of kuala lumpur': 'Kuala Lumpur',
    'labuan': 'Labuan',
    'wilayah persekutuan labuan': 'Labuan',
    'federal territory of labuan': 'Labuan',
    'melaka': 'Melaka',
    'malacca': 'Melaka',
    'negeri sembilan': 'Negeri Sembilan',
    'pahang': 'Pahang',
    'penang': 'Penang',
    'pulau pinang': 'Penang',
    'perak': 'Perak',
    'perlis': 'Perlis',
    'putrajaya': 'Putrajaya',
    'wilayah persekutuan putrajaya': 'Putrajaya',
    'federal territory of putrajaya': 'Putrajaya',
    'sabah': 'Sabah',
    'sarawak': 'Sarawak',
    'selangor': 'Selangor',
    'terengganu': 'Terengganu'
  };

  // Initialize or update the map
  function initializeMap(lat, lng) {
    if(mapWrapper) {
      mapWrapper.style.display = 'block';
    }

    // Create custom icon for the marker
    var customIcon = L.divIcon({
      className: 'custom-marker-container',
      html: '<div class="custom-marker" style="width: 24px; height: 24px;"></div>',
      iconSize: [24, 24],
      iconAnchor: [12, 12]
    });

    if(!locationMap) {
      // Initialize map
      locationMap = L.map('locationMap').setView([lat, lng], 17);
      
      // Add OpenStreetMap tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(locationMap);

      // Create draggable marker
      locationMarker = L.marker([lat, lng], {
        draggable: true,
        icon: customIcon
      }).addTo(locationMap);

      // When marker is dragged, update address
      locationMarker.on('dragend', function(e) {
        var position = e.target.getLatLng();
        showLocationStatus('Updating address...', 'loading');
        reverseGeocode(position.lat, position.lng);
      });

      // Also allow clicking on map to move marker
      locationMap.on('click', function(e) {
        locationMarker.setLatLng(e.latlng);
        showLocationStatus('Updating address...', 'loading');
        reverseGeocode(e.latlng.lat, e.latlng.lng);
      });
    } else {
      // Update existing map and marker position
      locationMap.setView([lat, lng], 17);
      locationMarker.setLatLng([lat, lng]);
    }

    // Invalidate size to fix rendering issues
    setTimeout(function() {
      locationMap.invalidateSize();
    }, 100);
  }

  function showLocationStatus(message, type) {
    if(locationStatus) {
      locationStatus.textContent = message;
      locationStatus.className = 'location-status ' + type;
      locationStatus.style.display = 'flex';
    }
  }

  function hideLocationStatus() {
    if(locationStatus) {
      locationStatus.style.display = 'none';
    }
  }

  function setLocationLoading(isLoading) {
    if(btnGetLocation) {
      btnGetLocation.disabled = isLoading;
    }
    if(locationSpinner) {
      locationSpinner.style.display = isLoading ? 'inline-flex' : 'none';
    }
    if(locationBtnText) {
      locationBtnText.textContent = isLoading ? 'Getting Location...' : 'Get Current Location';
    }
  }

  function matchMalaysianState(stateName) {
    if(!stateName) return '';
    var lowerState = stateName.toLowerCase().trim();
    return malaysianStates[lowerState] || '';
  }

  function fillAddressFromGeocodeResult(results) {
    if(!results || results.length === 0) {
      showLocationStatus('Could not find address for this location.', 'error');
      return;
    }

    var address = results[0];
    var components = address.address_components;
    
    var streetNumber = '';
    var route = '';
    var sublocality = '';
    var locality = '';
    var adminArea2 = '';
    var adminArea1 = '';
    var postalCode = '';
    var premise = '';
    var neighborhood = '';

    components.forEach(function(component) {
      var types = component.types;
      if(types.includes('street_number')) streetNumber = component.long_name;
      if(types.includes('route')) route = component.long_name;
      if(types.includes('sublocality') || types.includes('sublocality_level_1')) sublocality = component.long_name;
      if(types.includes('locality')) locality = component.long_name;
      if(types.includes('administrative_area_level_2')) adminArea2 = component.long_name;
      if(types.includes('administrative_area_level_1')) adminArea1 = component.long_name;
      if(types.includes('postal_code')) postalCode = component.long_name;
      if(types.includes('premise')) premise = component.long_name;
      if(types.includes('neighborhood')) neighborhood = component.long_name;
    });

    // Build address line 1
    var addressLine1Parts = [];
    if(streetNumber) addressLine1Parts.push(streetNumber);
    if(route) addressLine1Parts.push(route);
    if(addressLine1Parts.length === 0 && premise) addressLine1Parts.push(premise);
    if(addressLine1Parts.length === 0 && sublocality) addressLine1Parts.push(sublocality);
    
    var addressLine1 = addressLine1Parts.join(' ') || address.formatted_address.split(',')[0];
    
    // Build address line 2
    var addressLine2Parts = [];
    if(sublocality && !addressLine1.includes(sublocality)) addressLine2Parts.push(sublocality);
    if(neighborhood && !addressLine1.includes(neighborhood)) addressLine2Parts.push(neighborhood);
    var addressLine2 = addressLine2Parts.join(', ');

    // Determine city
    var city = locality || adminArea2 || sublocality || '';

    // Match state to dropdown options
    var matchedState = matchMalaysianState(adminArea1);

    // Fill form fields
    var address1Input = document.getElementById('address1');
    var address2Input = document.getElementById('address2');
    var cityInput = document.getElementById('city');
    var stateSelect = document.getElementById('state');
    var postcodeInput = document.getElementById('postcode');

    if(address1Input) address1Input.value = addressLine1;
    if(address2Input) address2Input.value = addressLine2;
    if(cityInput) cityInput.value = city;
    if(stateSelect && matchedState) {
      // Try to select the matching state option
      for(var i = 0; i < stateSelect.options.length; i++) {
        if(stateSelect.options[i].value === matchedState) {
          stateSelect.selectedIndex = i;
          break;
        }
      }
    }
    if(postcodeInput && postalCode) {
      // Ensure postcode is 5 digits for Malaysia
      var cleanPostcode = postalCode.replace(/\D/g, '').slice(0, 5);
      postcodeInput.value = cleanPostcode;
    }

    showLocationStatus('Address filled successfully! Please verify and adjust if needed.', 'success');
  }

  function reverseGeocode(lat, lng) {
    // Using Google Maps Geocoding API
    var apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Replace with your API key
    var geocodeUrl = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' + lat + ',' + lng + '&key=' + apiKey;

    // If no API key is set, use the free Nominatim service as fallback
    if(apiKey === 'YOUR_GOOGLE_MAPS_API_KEY') {
      // Use OpenStreetMap Nominatim (free, no API key needed)
      var nominatimUrl = 'https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&addressdetails=1';
      
      fetch(nominatimUrl, {
        headers: {
          'Accept-Language': 'en'
        }
      })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        setLocationLoading(false);
        if(data && data.address) {
          fillAddressFromNominatim(data);
        } else {
          showLocationStatus('Could not find address for this location.', 'error');
        }
      })
      .catch(function(error) {
        setLocationLoading(false);
        console.error('Geocoding error:', error);
        showLocationStatus('Failed to get address. Please try again.', 'error');
      });
      return;
    }

    // Use Google Maps API if key is provided
    fetch(geocodeUrl)
      .then(function(response) { return response.json(); })
      .then(function(data) {
        setLocationLoading(false);
        if(data.status === 'OK') {
          fillAddressFromGeocodeResult(data.results);
        } else {
          showLocationStatus('Geocoding failed: ' + data.status, 'error');
        }
      })
      .catch(function(error) {
        setLocationLoading(false);
        console.error('Geocoding error:', error);
        showLocationStatus('Failed to get address. Please try again.', 'error');
      });
  }

  function fillAddressFromNominatim(data) {
    var addr = data.address;
    
    // Build address line 1
    var addressLine1Parts = [];
    if(addr.house_number) addressLine1Parts.push(addr.house_number);
    if(addr.road) addressLine1Parts.push(addr.road);
    if(addressLine1Parts.length === 0 && addr.building) addressLine1Parts.push(addr.building);
    if(addressLine1Parts.length === 0 && addr.amenity) addressLine1Parts.push(addr.amenity);
    
    var addressLine1 = addressLine1Parts.join(' ') || data.display_name.split(',')[0];
    
    // Build address line 2
    var addressLine2Parts = [];
    if(addr.suburb) addressLine2Parts.push(addr.suburb);
    if(addr.neighbourhood && addr.neighbourhood !== addr.suburb) addressLine2Parts.push(addr.neighbourhood);
    var addressLine2 = addressLine2Parts.join(', ');

    // Determine city
    var city = addr.city || addr.town || addr.municipality || addr.village || addr.county || '';

    // Match state
    var stateName = addr.state || '';
    var matchedState = matchMalaysianState(stateName);

    // Get postcode
    var postcode = addr.postcode || '';

    // Fill form fields
    var address1Input = document.getElementById('address1');
    var address2Input = document.getElementById('address2');
    var cityInput = document.getElementById('city');
    var stateSelect = document.getElementById('state');
    var postcodeInput = document.getElementById('postcode');

    if(address1Input) address1Input.value = addressLine1;
    if(address2Input) address2Input.value = addressLine2;
    if(cityInput) cityInput.value = city;
    if(stateSelect && matchedState) {
      for(var i = 0; i < stateSelect.options.length; i++) {
        if(stateSelect.options[i].value === matchedState) {
          stateSelect.selectedIndex = i;
          break;
        }
      }
    }
    if(postcodeInput && postcode) {
      var cleanPostcode = postcode.replace(/\D/g, '').slice(0, 5);
      postcodeInput.value = cleanPostcode;
    }

    showLocationStatus('Address filled successfully! Please verify and adjust if needed.', 'success');
  }

  function getCurrentLocation() {
    hideLocationStatus();
    
    if(!navigator.geolocation) {
      showLocationStatus('Geolocation is not supported by your browser.', 'error');
      return;
    }

    setLocationLoading(true);
    showLocationStatus('Getting your location...', 'loading');

    navigator.geolocation.getCurrentPosition(
      function(position) {
        var lat = position.coords.latitude;
        var lng = position.coords.longitude;
        showLocationStatus('Location found! Getting address...', 'loading');
        // Initialize/update the map with the current position
        initializeMap(lat, lng);
        reverseGeocode(lat, lng);
      },
      function(error) {
        setLocationLoading(false);
        var errorMessage = 'Unable to get your location. ';
        switch(error.code) {
          case error.PERMISSION_DENIED:
            errorMessage += 'Please allow location access in your browser settings.';
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage += 'Location information is unavailable.';
            break;
          case error.TIMEOUT:
            errorMessage += 'The request timed out. Please try again.';
            break;
          default:
            errorMessage += 'An unknown error occurred.';
        }
        showLocationStatus(errorMessage, 'error');
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
      }
    );
  }

  if(btnGetLocation) {
    btnGetLocation.addEventListener('click', function(e) {
      e.preventDefault();
      getCurrentLocation();
    });
  }
})();
