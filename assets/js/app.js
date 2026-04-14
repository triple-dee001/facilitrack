/**
 * ============================================================
 * FaciliTrack — Client-Side JavaScript
 * Form validation, sidebar toggle, image preview, dynamic interactions
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initFormValidation();
    initCharCounters();
    initImagePreview();
    initAlertDismiss();
});

/* ============================================================
   SIDEBAR TOGGLE (Mobile)
   ============================================================ */
function initSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarClose = document.getElementById('sidebarClose');

    if (!menuToggle || !sidebar) return;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    menuToggle.addEventListener('click', function () {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }

    overlay.addEventListener('click', closeSidebar);
}

/* ============================================================
   PASSWORD TOGGLE
   ============================================================ */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.toggle-password i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/* ============================================================
   FORM VALIDATION
   ============================================================ */
function initFormValidation() {
    // Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            if (!validateEmail(email.value)) {
                e.preventDefault();
                showFieldError(email, 'Please enter a valid email address.');
                return;
            }

            if (password.value.length < 1) {
                e.preventDefault();
                showFieldError(password, 'Password is required.');
                return;
            }
        });
    }

    // Register Form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            let hasError = false;

            const fullName = document.getElementById('full_name');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const orgCode = document.getElementById('org_code');
            const location = document.getElementById('location');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            clearAllFieldErrors();

            if (fullName && fullName.value.trim().length < 2) {
                showFieldError(fullName, 'Full name is required.');
                hasError = true;
            }

            if (email && !validateEmail(email.value)) {
                showFieldError(email, 'Please enter a valid email.');
                hasError = true;
            }

            if (phone && phone.value.trim().length < 10) {
                showFieldError(phone, 'Enter a valid phone number.');
                hasError = true;
            }

            if (orgCode && orgCode.value.trim().length < 1) {
                showFieldError(orgCode, 'Organization code is required.');
                hasError = true;
            }

            if (location && location.value.trim().length < 1) {
                showFieldError(location, 'Location is required.');
                hasError = true;
            }

            if (password.value.length < 6) {
                showFieldError(password, 'Password must be at least 6 characters.');
                hasError = true;
            }

            if (password.value !== confirmPassword.value) {
                showFieldError(confirmPassword, 'Passwords do not match.');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });
    }

    // New Request Form
    const requestForm = document.getElementById('newRequestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', function (e) {
            let hasError = false;
            clearAllFieldErrors();

            const title = document.getElementById('title');
            const category = document.getElementById('category');
            const description = document.getElementById('description');

            if (title.value.trim().length < 3) {
                showFieldError(title, 'Title must be at least 3 characters.');
                hasError = true;
            }

            if (!category.value) {
                showFieldError(category, 'Please select a category.');
                hasError = true;
            }

            if (description.value.trim().length < 10) {
                showFieldError(description, 'Description must be at least 10 characters.');
                hasError = true;
            }

            if (hasError) {
                e.preventDefault();
            }
        });
    }
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showFieldError(input, message) {
    input.style.borderColor = '#EF4444';
    input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';

    // Remove existing error
    const existingError = input.parentElement.querySelector('.field-error');
    if (existingError) existingError.remove();

    const errorEl = document.createElement('span');
    errorEl.className = 'field-error';
    errorEl.style.color = '#EF4444';
    errorEl.style.fontSize = '12px';
    errorEl.style.marginTop = '4px';
    errorEl.textContent = message;

    input.parentElement.appendChild(errorEl);

    // Clear error on input
    input.addEventListener('input', function () {
        input.style.borderColor = '';
        input.style.boxShadow = '';
        const err = input.parentElement.querySelector('.field-error');
        if (err) err.remove();
    }, { once: true });
}

function clearAllFieldErrors() {
    document.querySelectorAll('.field-error').forEach(el => el.remove());
    document.querySelectorAll('input, select, textarea').forEach(el => {
        el.style.borderColor = '';
        el.style.boxShadow = '';
    });
}

/* ============================================================
   CHARACTER COUNTERS
   ============================================================ */
function initCharCounters() {
    const titleInput = document.getElementById('title');
    const titleCount = document.getElementById('titleCount');
    if (titleInput && titleCount) {
        titleCount.textContent = titleInput.value.length;
        titleInput.addEventListener('input', function () {
            titleCount.textContent = this.value.length;
            titleCount.style.color = this.value.length > 180 ? '#EF4444' : '';
        });
    }

    const descInput = document.getElementById('description');
    const descCount = document.getElementById('descCount');
    if (descInput && descCount) {
        descCount.textContent = descInput.value.length;
        descInput.addEventListener('input', function () {
            descCount.textContent = this.value.length;
            descCount.style.color = this.value.length > 1800 ? '#EF4444' : '';
        });
    }
}

/* ============================================================
   IMAGE PREVIEW
   ============================================================ */
function initImagePreview() {
    const fileInput = document.getElementById('image');
    const previewContainer = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const uploadArea = document.getElementById('fileUploadArea');

    if (!fileInput || !previewContainer || !previewImg) return;

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, GIF, or WebP).');
            this.value = '';
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image must be under 5MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
            if (uploadArea) uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
}

function clearImagePreview() {
    const fileInput = document.getElementById('image');
    const previewContainer = document.getElementById('imagePreview');
    const uploadArea = document.getElementById('fileUploadArea');

    if (fileInput) fileInput.value = '';
    if (previewContainer) previewContainer.style.display = 'none';
    if (uploadArea) uploadArea.style.display = '';
}

/* ============================================================
   ALERT AUTO-DISMISS
   ============================================================ */
function initAlertDismiss() {
    const alerts = document.querySelectorAll('.alert-success');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.4s ease';
            setTimeout(function () {
                alert.remove();
            }, 400);
        }, 5000);
    });
}
