(function(){
  // Get data attributes from body
  var userId = document.body.dataset.userId;
  var controllerUrl = document.body.dataset.controllerUrl;
  var prefix = document.body.dataset.prefix;

  // Photo Upload and Cropping
  var uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
  var photoUpload = document.getElementById('photoUpload');
  var photoCropperModal = document.getElementById('photoCropperModal');
  var cropperImage = document.getElementById('cropperImage');
  var btnCancelCrop = document.getElementById('btnCancelCrop');
  var btnSaveCrop = document.getElementById('btnSaveCrop');
  var cropper = null;
  var scaleX = 1;
  var scaleY = 1;

  // Trigger file input when camera badge is clicked
  if(uploadPhotoBtn) {
    uploadPhotoBtn.addEventListener('click', function(e) {
      e.preventDefault();
      photoUpload.click();
    });
  }

  // Handle file selection
  if(photoUpload) {
    photoUpload.addEventListener('change', function(e) {
      var file = e.target.files[0];
      if(file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(event) {
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
      photoUpload.value = '';
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
              photoUpload.value = '';
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
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      document.querySelector('.label-btn[data-label="home"]').classList.add('active');
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
    // Phone format
    var phoneOk = /^\d{3}-\d{3} \d{4}$/.test(contact.value.trim());
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
      if(addAddressModal.classList.contains('open')) { btnCancelAddAddress.click(); }
      else if(modal.classList.contains('open')) { closeModal(e); }
    }
  });
  if(saveBtn){
    saveBtn.addEventListener('click', function(e){
      var ok = validateForm();
      if(!ok){ e.preventDefault(); }
    });
  }
})();
