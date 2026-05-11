document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.js-toggle-password');
    
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = btn.querySelector('.material-symbols-outlined');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });

    // 2. Client-side Validation
    const validateForm = (form) => {
        let isValid = true;
        const inputs = form.querySelectorAll('.form__input');

        inputs.forEach(input => {
            const errorSpan = input.closest('.form__group').querySelector('.form__error');
            let errorMessage = '';

            // Reset state
            input.classList.remove('form__input--error');
            if (errorSpan) {
                errorSpan.classList.remove('form__error--visible');
                errorSpan.textContent = '';
            }

            // HTML5 Validation (required, email format)
            if (!input.checkValidity()) {
                isValid = false;
                if (input.validity.valueMissing) {
                    errorMessage = 'This field is required.';
                } else if (input.validity.typeMismatch && input.type === 'email') {
                    errorMessage = 'Please enter a valid email address.';
                }
            }

            // Custom Validation (Password length, Match)
            if (form.id === 'register-form' && input.id === 'password' && input.value.length > 0 && input.value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long.';
            }

            if (input.id === 'confirm-password') {
                const passwordValue = document.getElementById('password').value;
                if (input.value !== passwordValue) {
                    isValid = false;
                    errorMessage = 'Passwords do not match.';
                }
            }

            // Apply error styles
            if (errorMessage && errorSpan) {
                input.classList.add('form__input--error');
                errorSpan.textContent = errorMessage;
                errorSpan.classList.add('form__error--visible');
            }
        });

        return isValid;
    };

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            if (!validateForm(loginForm)) {
                e.preventDefault();
            }
        });
    }

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            if (!validateForm(registerForm)) {
                e.preventDefault();
            }
        });
    }
});