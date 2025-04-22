// Utility to show/hide elements
const toggleElement = (element, show, message = '') => {
    element.textContent = message;
    element.classList[show ? 'remove' : 'add']('hidden');
};

// Client-side validation functions
const validateRegisterForm = (formData) => {
    const username = formData.get('username')?.trim();
    const email = formData.get('email')?.trim();
    const phone = formData.get('phone_number')?.trim();
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm-password');

    if (!username || username.length < 3) return 'Username must be at least 3 characters.';
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email address.';
    if (!phone || !/^\+?[1-9]\d{1,14}$/.test(phone)) return 'Please enter a valid phone number.';
    if (!password || password.length < 6) return 'Password must be at least 6 characters.';
    if (password !== confirmPassword) return 'Passwords do not match.';
    return null;
};

const validateOtpForm = (formData) => {
    const otp = formData.get('otp')?.trim();
    if (!otp || !/^\d{6}$/.test(otp)) return 'Please enter a valid 6-digit OTP.';
    return null;
};

const validateLoginForm = (formData) => {
    const email = formData.get('email')?.trim();
    const password = formData.get('password');
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'Please enter a valid email address.';
    if (!password) return 'Password is required.';
    return null;
};

// Handle Registration Form
document.getElementById('register-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('form-error');
    const successDiv = document.getElementById('form-success');
    const loadingDiv = document.getElementById('form-loading');

    const validationError = validateRegisterForm(formData);
    if (validationError) {
        toggleElement(errorDiv, true, validationError);
        return;
    }

    toggleElement(errorDiv, false);
    toggleElement(successDiv, false);
    toggleElement(loadingDiv, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        toggleElement(loadingDiv, false);

        if (data.success) {
            toggleElement(successDiv, true, data.message);
            setTimeout(() => window.location.href = 'verify_otp.html', 2000);
        } else {
            toggleElement(errorDiv, true, data.message);
        }
    } catch (error) {
        toggleElement(loadingDiv, false);
        toggleElement(errorDiv, true, 'An error occurred. Please try again.');
    }
});

// Handle OTP Verification Form
document.getElementById('otp-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('form-error');
    const successDiv = document.getElementById('form-success');
    const loadingDiv = document.getElementById('form-loading');

    const validationError = validateOtpForm(formData);
    if (validationError) {
        toggleElement(errorDiv, true, validationError);
        return;
    }

    toggleElement(errorDiv, false);
    toggleElement(successDiv, false);
    toggleElement(loadingDiv, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        toggleElement(loadingDiv, false);

        if (data.success) {
            toggleElement(successDiv, true, data.message);
            setTimeout(() => window.location.href = 'login.html', 2000);
        } else {
            toggleElement(errorDiv, true, data.message);
        }
    } catch (error) {
        toggleElement(loadingDiv, false);
        toggleElement(errorDiv, true, 'An error occurred. Please try again.');
    }
});

// Handle Login Form
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('form-error');
    const successDiv = document.getElementById('form-success');
    const loadingDiv = document.getElementById('form-loading');

    const validationError = validateLoginForm(formData);
    if (validationError) {
        toggleElement(errorDiv, true, validationError);
        return;
    }

    toggleElement(errorDiv, false);
    toggleElement(successDiv, false);
    toggleElement(loadingDiv, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        toggleElement(loadingDiv, false);

        if (data.success) {
            toggleElement(successDiv, true, data.message);
            setTimeout(() => window.location.href = 'dashboard.php', 2000);
        } else {
            toggleElement(errorDiv, true, data.message);
        }
    } catch (error) {
        toggleElement(loadingDiv, false);
        toggleElement(errorDiv, true, 'An error occurred. Please try again.');
    }
});