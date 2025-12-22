$(document).ready(function() {
    // Get image base path from data attribute or use default
    const imageBasePath = $('body').data('image-base-path') || '';
    let searchTimeout;

    // AJAX Search with real-time filtering
    $('#filterSearch').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            performAjaxFilter();
        }, 500);
    });

    // AJAX Filter on form submit
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        performAjaxFilter();
    });

    // AJAX Filter on dropdown change
    $('#filterStatus, #filterSortBySelect, #filterSortOrderSelect').on('change', function() {
        performAjaxFilter();
    });

    function performAjaxFilter() {
        const searchTerm = $('#filterSearch').val().trim();
        const status = $('#filterStatus').val();
        const sortBy = $('#filterSortBySelect').val() || 'created_at';
        const sortOrder = $('#filterSortOrderSelect').val() || 'DESC';

        const tableWrapper = $('#members-table-wrapper');
        tableWrapper.css('opacity', '0.6');

        const requestData = {
            action: 'showAll',
            ajax: '1',
            search: searchTerm,
            status: status,
            sortBy: sortBy,
            sortOrder: sortOrder,
            page: 1
        };

        const controllerUrl = $('body').data('controller-url') || 'MemberController.php';

        $.ajax({
            url: controllerUrl,
            method: 'GET',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateTable(response);
                    updatePagination(response);
                } else {
                    alert('Error: ' + response.error);
                }
                tableWrapper.css('opacity', '1');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                alert('An error occurred while searching. Please try again.');
                tableWrapper.css('opacity', '1');
            }
        });
    }

    function updateTable(response) {
        const tbody = $('#members-table tbody');
        tbody.empty();
        selectedMembers.clear();
        $('#selectAllCheckbox').prop('checked', false);
        updateBulkActions();

        if (response.members && response.members.length > 0) {
            response.members.forEach(function(member) {
                const row = buildMemberRow(member);
                tbody.append(row);
            });
        } else {
            tbody.append(`
                <tr>
                    <td colspan="9" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No members found. Try a different search term.</p>
                        </div>
                    </td>
                </tr>
            `);
        }
    }

    function buildMemberRow(member) {
        let photoUrl = '';
        if (member.profile_photo && member.profile_photo.trim() !== '') {
            let photoPath = member.profile_photo;
            if (photoPath.indexOf('web/') === 0) {
                photoPath = photoPath.substring(4);
            }
            photoPath = photoPath.replace(/^\/+/, '');
            photoUrl = imageBasePath + photoPath;
        } else {
            photoUrl = imageBasePath + 'images/defaultUserImage.jpg';
        }
        const defaultPhotoUrl = imageBasePath + 'images/defaultUserImage.jpg';

        let dobDisplay = '-';
        if (member.DateOfBirth) {
            const dob = new Date(member.DateOfBirth);
            if (!isNaN(dob.getTime())) {
                dobDisplay = dob.toISOString().split('T')[0];
            }
        }

        let createdDateDisplay = '-';
        if (member.created_at) {
            const createdDate = new Date(member.created_at);
            if (!isNaN(createdDate.getTime())) {
                createdDateDisplay = createdDate.toISOString().split('T')[0];
            }
        }

        const status = member.status || 'active';
        const statusLabels = {
            'active': { class: 'status-active', text: 'Active' },
            'inactive': { class: 'status-inactive', text: 'Inactive' },
            'banned': { class: 'status-banned', text: 'Banned' },
            'blocked': { class: 'status-blocked', text: 'Blocked' }
        };
        const statusInfo = statusLabels[status] || statusLabels['active'];

        let statusButtons = '';
        if (status !== 'banned') {
            statusButtons += '<button class="action-btn ban-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="banned" title="Ban member"><i class="fas fa-ban"></i></button>';
        }
        if (status !== 'inactive') {
            statusButtons += '<button class="action-btn inactive-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="inactive" title="Set to inactive"><i class="fas fa-pause-circle"></i></button>';
        }
        if (status !== 'active') {
            statusButtons += '<button class="action-btn activate-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="active" title="Activate member"><i class="fas fa-check-circle"></i></button>';
        }

        const row = `
            <tr>
                <td class="col-checkbox">
                    <input type="checkbox" class="member-checkbox" name="member_ids[]" value="${member.user_id}" data-member-name="${escapeHtml(member.full_name)}">
                </td>
                <td>
                    <img src="${escapeHtml(photoUrl)}"
                         alt="Profile photo"
                         class="member-profile-photo clickable-image"
                         data-image-url="${escapeHtml(photoUrl)}"
                         data-member-name="${escapeHtml(member.full_name)}"
                         onerror="this.onerror=null; this.src='${escapeHtml(defaultPhotoUrl)}';"
                         style="cursor: pointer;"
                         title="Click to view full size">
                </td>
                <td>
                    <div>
                        <strong>${escapeHtml(member.username)}</strong>
                        <br><small style="color: #6b7280;">${escapeHtml(member.full_name)}</small>
                        <br><small style="color: #9ca3af;">${escapeHtml(member.email)}</small>
                    </div>
                </td>
                <td>${escapeHtml(member.contact_no)}</td>
                <td>${escapeHtml(member.gender)}</td>
                <td>${dobDisplay}</td>
                <td>${createdDateDisplay}</td>
                <td>
                    <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                </td>
                <td class="col-actions">
                    <div class="action-buttons">
                        <button class="action-btn edit-btn" data-user-id="${member.user_id}" data-username="${escapeHtml(member.username)}" data-full-name="${escapeHtml(member.full_name)}" data-email="${escapeHtml(member.email)}" data-contact-no="${escapeHtml(member.contact_no)}" data-gender="${escapeHtml(member.gender)}" data-date-of-birth="${escapeHtml(member.DateOfBirth || '')}" title="Edit member">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${statusButtons}
                        <button class="action-btn delete-btn" data-action="delete" data-user-id="${member.user_id}" data-user-name="${escapeHtml(member.full_name)}" title="Delete member">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return row;
    }

    function updatePagination(response) {
        const pagination = response.pagination;
        const paginationNav = $('.pagination');

        if (pagination.total_members > 0) {
            $('.pagination-info').html(`
                Showing <span class="pagination-number">${pagination.showing_from}-${pagination.showing_to}</span> of <span class="pagination-number">${pagination.total_members}</span>
            `);

            const paginationList = $('.pagination-list');
            paginationList.empty();

            const status = $('#filterStatus').val();
            const sortBy = response.sortBy || 'created_at';
            const sortOrder = response.sortOrder || 'DESC';
            const searchTerm = $('#simple-search').val();

            const controllerUrl = $('body').data('controller-url') || 'MemberController.php';

            const prevUrl = pagination.current_page > 1 ?
                `${controllerUrl}?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
            paginationList.append(`
                <li>
                    ${pagination.current_page > 1 ?
                        `<a href="${prevUrl}" class="pagination-link pagination-prev"><span class="material-symbols-outlined">chevron_left</span></a>` :
                        `<span class="pagination-link pagination-prev pagination-disabled"><span class="material-symbols-outlined">chevron_left</span></span>`
                    }
                </li>
            `);

            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === pagination.current_page ? 'pagination-active' : '';
                const pageUrl = `${controllerUrl}?action=showAll&page=${i}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}`;
                paginationList.append(`
                    <li>
                        <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                    </li>
                `);
            }

            const nextUrl = pagination.current_page < pagination.total_pages ?
                `${controllerUrl}?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
            paginationList.append(`
                <li>
                    ${pagination.current_page < pagination.total_pages ?
                        `<a href="${nextUrl}" class="pagination-link pagination-next"><span class="material-symbols-outlined">chevron_right</span></a>` :
                        `<span class="pagination-link pagination-next pagination-disabled"><span class="material-symbols-outlined">chevron_right</span></span>`
                    }
                </li>
            `);

            paginationNav.show();
        } else {
            paginationNav.hide();
        }
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function openEditModal(userId, username, fullName, email, contactNo, gender, dateOfBirth) {
        $('#editUserId').val(userId);
        $('#editUsername').val(username);
        $('#editFullName').val(fullName);
        $('#editEmail').val(email);
        
        // Display contact number (remove any country code prefix if present)
        let phoneNumber = contactNo || '';
        // Remove country code prefixes if they exist
        phoneNumber = phoneNumber.replace(/^\+60\s*/, '').replace(/^\+1\s*/, '').replace(/^\+44\s*/, '').replace(/^\+65\s*/, '').replace(/^\+86\s*/, '').replace(/^\+81\s*/, '').replace(/^\+61\s*/, '').replace(/^\+33\s*/, '').replace(/^\+49\s*/, '');
        $('#editPhoneNumber').val(phoneNumber.trim());
        
        $('#editGender').val(gender);
        $('#editDateOfBirth').val(dateOfBirth || '');
        
        $('#editModal').removeClass('hidden');
    }

    function closeEditModal() {
        $('#editModal').addClass('hidden');
    }

    // Malaysian phone number validation only
    const malaysiaPattern = /^0?[0-9]{2,3}[- ]?[0-9]{3,4}[- ]?[0-9]{4}$/;
    const example = '011-5550 5761';

    function validateEditPhoneNumber() {
        const phoneNumber = $('#editPhoneNumber').val().replace(/\s+/g, ' ').trim();
        const $phoneNumber = $('#editPhoneNumber');
        const $phoneValidationError = $('#editPhoneValidationError');

        if (!phoneNumber) {
            $phoneNumber.removeClass('input-error input-success');
            $phoneValidationError.text('').hide();
            $('#editContactNo').val('');
            return false;
        }

        // Remove spaces and dashes for validation
        const cleanPhone = phoneNumber.replace(/[- ]/g, '');
        
        // Check length (10-11 digits for Malaysia)
        if (cleanPhone.length < 10 || cleanPhone.length > 11) {
            $phoneNumber.addClass('input-error').removeClass('input-success');
            $phoneValidationError.text('Malaysian phone number must be 10-11 digits. Example: ' + example).show();
            $('#editContactNo').val('');
            return false;
        }

        // Check pattern
        if (!malaysiaPattern.test(phoneNumber)) {
            $phoneNumber.addClass('input-error').removeClass('input-success');
            $phoneValidationError.text('Invalid Malaysian phone format. Example: ' + example).show();
            $('#editContactNo').val('');
            return false;
        }

        // Valid phone number - store digits only
        $phoneNumber.removeClass('input-error').addClass('input-success');
        $phoneValidationError.text('').hide();
        $('#editContactNo').val(cleanPhone);
        
        return true;
    }

    // Phone validation event handlers
    $(document).on('input', '#editPhoneNumber', function() {
        validateEditPhoneNumber();
    });

    // Form submission validation
    $(document).on('submit', '#editForm', function(e) {
        if (!validateEditPhoneNumber()) {
            e.preventDefault();
            alert('Please enter a valid Malaysian phone number (10-11 digits).');
            $('#editPhoneNumber').focus();
            return false;
        }
        return true;
    });

    function confirmStatusChange(userId, userName, newStatus) {
        const statusLabels = {
            'active': 'activate',
            'inactive': 'set to inactive',
            'banned': 'ban'
        };
        const action = statusLabels[newStatus] || newStatus;

        showConfirmationModal(
            'Are you sure you want to ' + action + ' member: ' + userName + '?',
            'warning',
            function() {
                $('#statusUserId').val(userId);
                $('#statusValue').val(newStatus);
                $('#statusForm').submit();
            }
        );
    }

    function confirmDelete(userId, userName) {
        showConfirmationModal(
            'Are you sure you want to delete member: ' + userName + '?<br><br>This action cannot be undone.',
            'danger',
            function() {
                $('#deleteUserId').val(userId);
                $('#deleteForm').submit();
            }
        );
    }

    function showConfirmationModal(message, type, onConfirm) {
        type = type || 'warning';
        const iconClass = type === 'danger' ? 'danger' : 'warning';
        const confirmBtnClass = type === 'danger' ? 'confirmation-modal-btn-confirm' : 'confirmation-modal-btn-confirm warning';
        const confirmText = type === 'danger' ? 'Delete' : 'Confirm';

        $('#confirmationModalMessage').html(message);
        $('#confirmationModalIcon').removeClass('warning danger').addClass(iconClass);
        $('#confirmationModalConfirmBtn').removeClass('warning confirmation-modal-btn-confirm').addClass(confirmBtnClass).text(confirmText);

        // Store the confirm callback
        $('#confirmationModalConfirmBtn').off('click').on('click', function() {
            hideConfirmationModal();
            if (onConfirm) onConfirm();
        });

        $('#confirmationModal').addClass('show');
        $('body').css('overflow', 'hidden');
    }

    function hideConfirmationModal() {
        $('#confirmationModal').removeClass('show');
        $('body').css('overflow', 'auto');
    }

    function viewMemberImage(imageUrl, memberName) {
        $('#viewImageSrc').attr('src', imageUrl);
        $('#viewImageTitle').text(memberName + ' - Profile Photo');
        $('#viewImageModal').removeClass('hidden');
        $('body').css('overflow', 'hidden');
    }

    function closeImageViewModal() {
        $('#viewImageModal').addClass('hidden');
        $('body').css('overflow', 'auto');
    }

    $(document).on('click', '#viewImageModal .image-modal-overlay', function(e) {
        if (e.target === this) {
            closeImageViewModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#viewImageModal').hasClass('hidden')) {
            closeImageViewModal();
        }
    });

    $(document).on('click', '.btn-close-edit-modal', function() {
        closeEditModal();
    });

    $(document).on('click', '.btn-close-image-modal', function() {
        closeImageViewModal();
    });

    $(document).on('click', '.edit-btn', function() {
        const $btn = $(this);
        openEditModal(
            $btn.data('user-id'),
            $btn.data('username'),
            $btn.data('full-name'),
            $btn.data('email'),
            $btn.data('contact-no'),
            $btn.data('gender'),
            $btn.data('date-of-birth')
        );
    });

    $(document).on('click', '.action-btn[data-action="status"]', function() {
        const $btn = $(this);
        confirmStatusChange(
            $btn.data('user-id'),
            $btn.data('user-name'),
            $btn.data('status')
        );
    });

    $(document).on('click', '.action-btn[data-action="delete"]', function() {
        const $btn = $(this);
        confirmDelete(
            $btn.data('user-id'),
            $btn.data('user-name')
        );
    });

    $(document).on('click', '.clickable-image', function() {
        const $img = $(this);
        viewMemberImage(
            $img.data('image-url'),
            $img.data('member-name')
        );
    });

    let selectedMembers = new Set();

    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.member-checkbox').prop('checked', isChecked);

        if (isChecked) {
            $('.member-checkbox').each(function() {
                selectedMembers.add($(this).val());
            });
        } else {
            selectedMembers.clear();
        }

        updateBulkActions();
    });

    $(document).on('change', '.member-checkbox', function() {
        const memberId = $(this).val();
        if ($(this).is(':checked')) {
            selectedMembers.add(memberId);
        } else {
            selectedMembers.delete(memberId);
            $('#selectAllCheckbox').prop('checked', false);
        }
        updateBulkActions();
    });

    function updateBulkActions() {
        const count = selectedMembers.size;
        $('#selectedCount').text(count);

        if (count > 0) {
            $('#bulkActionsSection').show();
        } else {
            $('#bulkActionsSection').hide();
        }
    }

    $('#clearSelectionBtn').on('click', function() {
        $('.member-checkbox').prop('checked', false);
        $('#selectAllCheckbox').prop('checked', false);
        selectedMembers.clear();
        updateBulkActions();
    });

    $('#bulkDeleteBtn').on('click', function() {
        const count = selectedMembers.size;
        if (count === 0) {
            alert('Please select at least one member to delete.');
            return;
        }

        showConfirmationModal(
            `Are you sure you want to delete ${count} member(s)?<br><br>This action cannot be undone.`,
            'danger',
            function() {
                $('#bulkDeleteForm input[name="user_ids[]"]').remove();

                selectedMembers.forEach(function(memberId) {
                    $('#bulkDeleteForm').append(`<input type="hidden" name="user_ids[]" value="${memberId}">`);
                });

                $('#bulkDeleteForm').submit();
            }
        );
    });

    // Close confirmation modal on overlay click
    $(document).on('click', '#confirmationModal', function(e) {
        if ($(e.target).hasClass('confirmation-modal-overlay')) {
            hideConfirmationModal();
        }
    });

    // Close confirmation modal on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#confirmationModal').hasClass('show')) {
            hideConfirmationModal();
        }
    });

    function validateAddAdminPhoneNumber() {
        const phoneNumber = $('#addAdminPhoneNumber').val().replace(/\s+/g, ' ').trim();
        const $phoneNumber = $('#addAdminPhoneNumber');
        const $phoneValidationError = $('#addAdminPhoneValidationError');

        if (!phoneNumber) {
            $phoneNumber.removeClass('input-error input-success');
            $phoneValidationError.text('').hide();
            return false;
        }

        // Remove spaces and dashes for validation
        const cleanPhone = phoneNumber.replace(/[- ]/g, '');
        
        // Check length (10-11 digits for Malaysia)
        if (cleanPhone.length < 10 || cleanPhone.length > 11) {
            $phoneNumber.addClass('input-error').removeClass('input-success');
            $phoneValidationError.text('Malaysian phone number must be 10-11 digits. Example: ' + example).show();
            return false;
        }

        // Check pattern
        if (!malaysiaPattern.test(phoneNumber)) {
            $phoneNumber.addClass('input-error').removeClass('input-success');
            $phoneValidationError.text('Invalid Malaysian phone format. Example: ' + example).show();
            return false;
        }

        // Valid phone number
        $phoneNumber.removeClass('input-error').addClass('input-success');
        $phoneValidationError.text('').hide();
        
        return true;
    }

    // Phone validation event handlers for add admin
    $(document).on('input', '#addAdminPhoneNumber', function() {
        validateAddAdminPhoneNumber();
    });

    // Add Admin form validation
    $('#addAdminForm').on('submit', function(e) {
        let isValid = true;
        let errorMessage = '';

        // Validate username
        const username = $('#addAdminUsername').val().trim();
        if (!username || username.length < 3 || !/^[a-zA-Z0-9._-]+$/.test(username)) {
            isValid = false;
            errorMessage = 'Please enter a valid username (at least 3 characters, alphanumeric with . _ -).';
            $('#addAdminUsername').focus();
        }

        // Validate email
        const email = $('#addAdminEmail').val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            isValid = false;
            if (!errorMessage) {
                errorMessage = 'Please enter a valid email address.';
                $('#addAdminEmail').focus();
            }
        }

        // Validate password
        const pwd = $('#addAdminPassword').val() || '';
        const passwordStrength = checkPasswordStrength(pwd);
        if (passwordStrength.strength < 4) {
            isValid = false;
            if (!errorMessage) {
                errorMessage = 'Password must meet all requirements (8+ chars, uppercase, lowercase, number, special character).';
                $('#addAdminPassword').focus();
            }
        }

        // Validate password match
        const repeat = $('#addAdminRepeatPassword').val() || '';
        if (pwd !== repeat) {
            isValid = false;
            if (!errorMessage) {
                errorMessage = 'Passwords do not match.';
                $('#addAdminRepeatPassword').focus();
            }
        }

        // Validate phone number
        if (!validateAddAdminPhoneNumber()) {
            isValid = false;
            if (!errorMessage) {
                errorMessage = 'Please enter a valid Malaysian phone number (10-11 digits).';
                $('#addAdminPhoneNumber').focus();
            }
        }

        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
            return false;
        }

        return true;
    });

    // Password visibility toggle
    $('#togglePassword').on('click', function() {
        const passwordInput = $('#addAdminPassword');
        const icon = $(this).find('.material-symbols-outlined');
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.text('visibility_off');
        } else {
            passwordInput.attr('type', 'password');
            icon.text('visibility');
        }
    });

    $('#toggleRepeatPassword').on('click', function() {
        const passwordInput = $('#addAdminRepeatPassword');
        const icon = $(this).find('.material-symbols-outlined');
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.text('visibility_off');
        } else {
            passwordInput.attr('type', 'password');
            icon.text('visibility');
        }
    });

    // Password strength checker
    function checkPasswordStrength(password) {
        const checks = {
            length: password ? password.length >= 8 : false,
            upper: password ? /[A-Z]/.test(password) : false,
            lower: password ? /[a-z]/.test(password) : false,
            number: password ? /[0-9]/.test(password) : false,
            special: password ? /[!@#$%^&*]/.test(password) : false
        };

        if (!password) {
            return { strength: 0, text: 'Enter a password', class: '', checks };
        }

        let strength = Object.values(checks).filter(Boolean).length;

        let strengthText = '';
        let strengthClass = '';
        
        if (strength <= 2) {
            strengthText = 'Weak';
            strengthClass = 'weak';
        } else if (strength === 3) {
            strengthText = 'Fair';
            strengthClass = 'fair';
        } else if (strength === 4) {
            strengthText = 'Good';
            strengthClass = 'good';
        } else {
            strengthText = 'Strong';
            strengthClass = 'strong';
        }

        return { strength, text: strengthText, class: strengthClass, checks };
    }

    function updatePasswordStrength(password) {
        const result = checkPasswordStrength(password);
        const $indicator = $('#passwordStrengthIndicator');
        const $bars = $('.password-strength-bar');
        const $text = $('#passwordStrengthText');

        if (password) {
            $indicator.show();
            $bars.removeClass('active weak fair good strong');
            
            for (let i = 0; i < result.strength; i++) {
                $bars.eq(i).addClass('active ' + result.class);
            }

            $text.text('Password strength: ' + result.text);
        } else {
            $indicator.hide();
        }

        // Update requirements
        updatePasswordRequirements(result.checks);
    }

    function updatePasswordRequirements(checks) {
        $('#reqLength').toggleClass('met', checks.length);
        $('#reqLength .material-symbols-outlined').text(checks.length ? 'check_circle' : 'close');
        
        $('#reqUpper').toggleClass('met', checks.upper);
        $('#reqUpper .material-symbols-outlined').text(checks.upper ? 'check_circle' : 'close');
        
        $('#reqLower').toggleClass('met', checks.lower);
        $('#reqLower .material-symbols-outlined').text(checks.lower ? 'check_circle' : 'close');
        
        $('#reqNumber').toggleClass('met', checks.number);
        $('#reqNumber .material-symbols-outlined').text(checks.number ? 'check_circle' : 'close');
        
        $('#reqSpecial').toggleClass('met', checks.special);
        $('#reqSpecial .material-symbols-outlined').text(checks.special ? 'check_circle' : 'close');
    }

    // Real-time password validation
    $('#addAdminPassword').on('input', function() {
        const password = $(this).val();
        updatePasswordStrength(password);
        validatePassword();
        checkPasswordMatch();
    });

    $('#addAdminRepeatPassword').on('input', function() {
        checkPasswordMatch();
    });

    function validatePassword() {
        const password = $('#addAdminPassword').val();
        const result = checkPasswordStrength(password);
        const $input = $('#addAdminPassword');
        const $icon = $('#passwordValidationIcon');

        if (!password) {
            $input.removeClass('input-error input-success');
            $icon.hide();
            return false;
        }

        if (result.strength >= 4) {
            $input.removeClass('input-error').addClass('input-success');
            $icon.removeClass('error').addClass('success').text('check_circle').show();
            return true;
        } else {
            $input.removeClass('input-success').addClass('input-error');
            $icon.removeClass('success').addClass('error').text('error').show();
            return false;
        }
    }

    function checkPasswordMatch() {
        const password = $('#addAdminPassword').val();
        const repeatPassword = $('#addAdminRepeatPassword').val();
        const $repeatInput = $('#addAdminRepeatPassword');
        const $icon = $('#repeatPasswordValidationIcon');
        const $message = $('#passwordMatchMessage');

        if (!repeatPassword) {
            $repeatInput.removeClass('input-error input-success');
            $icon.hide();
            $message.hide();
            return false;
        }

        if (password === repeatPassword && password) {
            $repeatInput.removeClass('input-error').addClass('input-success');
            $icon.removeClass('error').addClass('success').text('check_circle').show();
            $message.removeClass('input-error').addClass('input-success').html('<span class="material-symbols-outlined">check_circle</span><span>Passwords match</span>').show();
            return true;
        } else {
            $repeatInput.removeClass('input-success').addClass('input-error');
            $icon.removeClass('success').addClass('error').text('error').show();
            $message.removeClass('input-success').addClass('input-error').html('<span class="material-symbols-outlined">error</span><span>Passwords do not match</span>').show();
            return false;
        }
    }

    // Email validation
    $('#addAdminEmail').on('blur', function() {
        const email = $(this).val();
        const $input = $(this);
        const $icon = $('#emailValidationIcon');

        if (!email) {
            $icon.hide();
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailRegex.test(email)) {
            $input.removeClass('input-error').addClass('input-success');
            $icon.removeClass('error').addClass('success').text('check_circle').show();
        } else {
            $input.removeClass('input-success').addClass('input-error');
            $icon.removeClass('success').addClass('error').text('error').show();
        }
    });

    // Username validation
    $('#addAdminUsername').on('blur', function() {
        const username = $(this).val();
        const $input = $(this);
        const $icon = $('#usernameValidationIcon');

        if (!username) {
            $icon.hide();
            return;
        }

        if (username.length >= 3 && /^[a-zA-Z0-9._-]+$/.test(username)) {
            $input.removeClass('input-error').addClass('input-success');
            $icon.removeClass('error').addClass('success').text('check_circle').show();
        } else {
            $input.removeClass('input-success').addClass('input-error');
            $icon.removeClass('success').addClass('error').text('error').show();
        }
    });

    // Function to open add admin modal
    function openAddAdminModal() {
        const form = $('#addAdminForm');
        if (form.length) {
            form[0].reset();
        }
        
        // Reset phone fields
        $('#addAdminPhoneNumber').val('');
        $('#addAdminPhoneValidationError').text('').hide();
        $('#addAdminPhoneNumber').removeClass('input-error input-success');
        
        // Reset password fields
        $('#addAdminPassword').attr('type', 'password');
        $('#addAdminRepeatPassword').attr('type', 'password');
        $('#togglePassword .material-symbols-outlined').text('visibility');
        $('#toggleRepeatPassword .material-symbols-outlined').text('visibility');
        $('#passwordStrengthIndicator').hide();
        $('.password-requirement').removeClass('met');
        $('.password-requirement .material-symbols-outlined').text('close');
        $('#passwordMatchMessage').hide();
        
        // Reset validation icons
        $('.validation-icon').hide();
        $('.form-input').removeClass('input-error input-success');
        
        $('#addAdminModal').removeClass('hidden');
    }
    
    // Add admin modal - Use event delegation to work in iframe context
    $(document).on('click', '#openAddAdminBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openAddAdminModal();
    });
    
    // Also bind directly (for iframe compatibility)
    setTimeout(function() {
        const btn = $('#openAddAdminBtn');
        if (btn.length) {
            btn.off('click.addAdmin').on('click.addAdmin', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openAddAdminModal();
            });
        }
    }, 200);

    $(document).on('click', '.btn-close-add-modal', function() {
        $('#addAdminModal').addClass('hidden');
    });

    // Close modal on overlay click
    $(document).on('click', '#addAdminModal', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
            $('#addAdminModal').addClass('hidden');
        }
    });
});

