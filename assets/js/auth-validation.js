document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('form[action="register.php"]');
    if (!form) return; 

    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');

    function showError(field, message) {
        clearError(field);
        const error = document.createElement('small');
        error.className = 'field-error';
        error.textContent = message;
        field.parentNode.insertBefore(error, field.nextSibling);
        field.classList.add('input-invalid');
    }

    function clearError(field) {
        const existing = field.parentNode.querySelector('.field-error');
        if (existing) existing.remove();
        field.classList.remove('input-invalid');
    }

    function showValid(field) {
        clearError(field);
        field.classList.add('input-valid');
    }

    // ----- Validators -----
    function validateName() {
        if (fullName.value.trim().length < 2) {
            showError(fullName, 'Name must be at least 2 characters.');
            return false;
        }
        showValid(fullName);
        return true;
    }

    function validateEmail() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) {
            showError(email, 'Please enter a valid email address.');
            return false;
        }
        showValid(email);
        return true;
    }

    function validatePhone() {
        const phoneRegex = /^[0-9+\s\-]{10,15}$/;
        if (!phoneRegex.test(phone.value.trim())) {
            showError(phone, 'Phone number must be 10-15 digits.');
            return false;
        }
        showValid(phone);
        return true;
    }

    // ----- Password strength meter -----
    function calculateStrength(pwd) {
        let score = 0;
        if (pwd.length >= 8) score++;
        if (pwd.length >= 12) score++;
        if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^a-zA-Z0-9]/.test(pwd)) score++;
        return score; // 0-5
    }

    function updateStrengthMeter() {
        let meter = document.getElementById('strength-meter');
        if (!meter) {
            // Create the meter element if it doesn't exist
            meter = document.createElement('div');
            meter.id = 'strength-meter';
            meter.className = 'strength-meter';
            meter.innerHTML = '<div class="strength-bar"></div><span class="strength-label"></span>';
            password.parentNode.insertBefore(meter, password.nextSibling);
        }

        const score = calculateStrength(password.value);
        const bar = meter.querySelector('.strength-bar');
        const label = meter.querySelector('.strength-label');

        const levels = [
            { width: '0%',   color: '#e2e8f0', text: '' },
            { width: '20%',  color: '#dc2626', text: 'Very weak' },
            { width: '40%',  color: '#ea580c', text: 'Weak' },
            { width: '60%',  color: '#f59e0b', text: 'Fair' },
            { width: '80%',  color: '#16a34a', text: 'Strong' },
            { width: '100%', color: '#15803d', text: 'Very strong' }
        ];

        const level = levels[score];
        bar.style.width = level.width;
        bar.style.background = level.color;
        label.textContent = level.text;
        label.style.color = level.color;
    }

    function validatePassword() {
        if (password.value.length < 8) {
            showError(password, 'Password must be at least 8 characters.');
            return false;
        }
        showValid(password);
        return true;
    }

    function validateMatch() {
        if (confirm.value === '') return false;
        if (password.value !== confirm.value) {
            showError(confirm, 'Passwords do not match.');
            return false;
        }
        showValid(confirm);
        return true;
    }

    fullName.addEventListener('blur', validateName);
    email.addEventListener('blur', validateEmail);
    phone.addEventListener('blur', validatePhone);

    password.addEventListener('input', function () {
        updateStrengthMeter();
        if (password.value.length >= 8) validatePassword();
        if (confirm.value) validateMatch();
    });

    confirm.addEventListener('input', validateMatch);

    form.addEventListener('submit', function (e) {
        const isValid =
            validateName() &
            validateEmail() &
            validatePhone() &
            validatePassword() &
            validateMatch();

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});