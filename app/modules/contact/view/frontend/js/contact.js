/**
 * Contact Page JavaScript
 */

(function() {
    'use strict';

    let initialized = false;

    /**
     * Lightweight initial setup - only attach interaction listener
     */
    function initLazy() {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        // Wait for user to focus on ANY form element
        form.addEventListener('focusin', function() {
            if (!initialized) {
                initialized = true;
                loadFullValidation();
            }
        }, { once: true, capture: true });

        // Also initialize if user submits without focusing (edge case)
        form.addEventListener('submit', function(e) {
            if (!initialized) {
                e.preventDefault();
                initialized = true;
                loadFullValidation();
                // Re-submit after validation loads
                setTimeout(() => form.dispatchEvent(new Event('submit', { bubbles: true })), 100);
            }
        }, { once: true });
    }

    /**
     * Load full validation logic only when needed
     */
    function loadFullValidation() {
        console.log('🚀 Loading form validation (lazy)');
        loadReCaptcha();
        initFormValidation();
        initFormSubmission();
    }

    /**
     * Lazy load reCAPTCHA only when user interacts with form
     */
    function loadReCaptcha() {
        // Check if reCAPTCHA is needed
        const recaptchaToken = document.getElementById('recaptchaToken');
        if (!recaptchaToken) return; // reCAPTCHA not enabled
        
        // Check if already loaded
        if (window.grecaptcha) {
            executeReCaptcha();
            return;
        }

        // Get site key from data attribute
        const form = document.querySelector('.contact-form');
        const siteKey = recaptchaToken.dataset.sitekey;
        if (!siteKey) {
            console.warn('reCAPTCHA site key not found');
            return;
        }

        console.log('🔐 Loading reCAPTCHA lazily...');

        // Load reCAPTCHA script
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?render=${siteKey}`;
        script.async = true;
        script.defer = true;
        script.onload = executeReCaptcha;
        document.head.appendChild(script);
    }

    /**
     * Execute reCAPTCHA token generation
     */
    function executeReCaptcha() {
        const recaptchaToken = document.getElementById('recaptchaToken');
        const siteKey = recaptchaToken.dataset.sitekey;
        
        if (window.grecaptcha && window.grecaptcha.ready) {
            grecaptcha.ready(function() {
                grecaptcha.execute(siteKey, {action: 'contact_form'}).then(function(token) {
                    recaptchaToken.value = token;
                    console.log('✓ reCAPTCHA token generated');
                });
            });
        }
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        const inputs = form.querySelectorAll('.form-input, .form-textarea');
        
        inputs.forEach(input => {
            // Validate on blur
            input.addEventListener('blur', function() {
                validateField(this);
            });

            // Clear error on input
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

    /**
     * Validate a single field
     */
    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        let isValid = true;
        let errorMessage = '';

        // Required field check
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        // Phone validation (basic - allows common formats)
        else if (type === 'tel' && value) {
            const phoneRegex = /^[\d\s\-\(\)\+\.]{7,20}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        }

        if (!isValid) {
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        // Remove existing error
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) existingError.remove();

        // Add error class
        field.classList.add('invalid');

        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        formGroup.appendChild(errorDiv);
    }

    /**
     * Clear field error
     */
    function clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        field.classList.remove('invalid');
        const error = formGroup.querySelector('.field-error');
        if (error) error.remove();
    }

    /**
     * Initialize AJAX form submission
     */
    function initFormSubmission() {
        const form = document.querySelector('.contact-form');
        if (!form) return;

        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submissions
            if (isSubmitting) return;

            // Validate all fields
            const inputs = form.querySelectorAll('.form-input, .form-textarea');
            let isValid = true;

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                showMessage('Please fix the errors above', 'error');
                return;
            }

            // Submit form
            isSubmitting = true;
            submitForm(form).finally(() => {
                isSubmitting = false;
            });
        });
    }

    /**
     * Submit form via AJAX
     * @returns {Promise}
     */
    function submitForm(form) {
        const submitButton = form.querySelector('.form-submit');
        const originalText = submitButton.innerHTML;
        
        // Disable button and show loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<span>Sending...</span>';

        const formData = new FormData(form);

        return fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage('Message sent successfully! We\'ll get back to you soon.', 'success');
                form.reset();
            } else {
                showMessage(data.message || 'An error occurred. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            showMessage('An error occurred. Please try again.', 'error');
        })
        .finally(() => {
            // Re-enable button
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    }

    /**
     * Show modern notification message
     */
    function showMessage(message, type) {
        // Remove existing message
        const existing = document.querySelector('.form-message');
        if (existing) existing.remove();

        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message ${type}`;
        messageDiv.setAttribute('role', 'alert');
        messageDiv.setAttribute('aria-live', 'polite');
        
        // Add icon in circular badge
        const icon = document.createElement('span');
        icon.className = 'form-message-icon';
        icon.textContent = type === 'success' ? '✓' : '✕';
        messageDiv.appendChild(icon);
        
        // Add message text
        const text = document.createElement('span');
        text.textContent = message;
        messageDiv.appendChild(text);

        // Append to body (position:fixed, so location doesn't matter)
        document.body.appendChild(messageDiv);

        // Auto-remove after duration (longer for success messages)
        const duration = type === 'success' ? 8000 : 6000;
        setTimeout(() => {
            messageDiv.classList.add('fade-out');
            setTimeout(() => messageDiv.remove(), 500);
        }, duration);
    }

    // Initialize lazy loading when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLazy);
    } else {
        initLazy();
    }
})();
