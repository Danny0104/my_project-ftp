/**
 * Modern UI JavaScript Library
 * Provides interactive components and utilities
 */

class ModernUI {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
        this.setupNotifications();
    }

    setupEventListeners() {
        // Mobile menu toggle
        const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
        const mobileMenu = document.querySelector('[data-mobile-menu]');
        
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Dropdown toggles
        document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const dropdown = document.querySelector(toggle.getAttribute('data-dropdown-toggle'));
                if (dropdown) {
                    dropdown.classList.toggle('hidden');
                }
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[data-dropdown]')) {
                document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });

        // Modal toggles
        document.querySelectorAll('[data-modal-toggle]').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = document.querySelector(toggle.getAttribute('data-modal-toggle'));
                if (modal) {
                    this.showModal(modal);
                }
            });
        });

        // Close modals
        document.querySelectorAll('[data-modal-close]').forEach(close => {
            close.addEventListener('click', (e) => {
                e.preventDefault();
                const modal = close.closest('[data-modal]');
                if (modal) {
                    this.hideModal(modal);
                }
            });
        });

        // Auto-hide alerts
        document.querySelectorAll('.alert[data-auto-hide]').forEach(alert => {
            const delay = parseInt(alert.getAttribute('data-auto-hide')) || 5000;
            setTimeout(() => {
                this.hideAlert(alert);
            }, delay);
        });
    }

    initializeComponents() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize loading states
        this.initLoadingStates();
        
        // Initialize animations
        this.initAnimations();
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    setupNotifications() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('#notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
        }
    }

    // Form Validation
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    validateInput(input) {
        const value = input.value.trim();
        const type = input.type;
        const required = input.hasAttribute('required');
        let isValid = true;
        
        // Clear previous validation states
        input.classList.remove('is-invalid', 'is-valid');
        this.clearValidationMessage(input);
        
        // Required validation
        if (required && !value) {
            this.showValidationError(input, 'This field is required');
            isValid = false;
        }
        
        // Type-specific validation
        if (value && type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showValidationError(input, 'Please enter a valid email address');
                isValid = false;
            }
        }
        
        if (value && type === 'password') {
            if (value.length < 8) {
                this.showValidationError(input, 'Password must be at least 8 characters long');
                isValid = false;
            }
        }
        
        if (value && input.hasAttribute('data-min-length')) {
            const minLength = parseInt(input.getAttribute('data-min-length'));
            if (value.length < minLength) {
                this.showValidationError(input, `Must be at least ${minLength} characters long`);
                isValid = false;
            }
        }
        
        if (value && input.hasAttribute('data-max-length')) {
            const maxLength = parseInt(input.getAttribute('data-max-length'));
            if (value.length > maxLength) {
                this.showValidationError(input, `Must be no more than ${maxLength} characters long`);
                isValid = false;
            }
        }
        
        if (isValid && value) {
            input.classList.add('is-valid');
        }
        
        return isValid;
    }

    showValidationError(input, message) {
        input.classList.add('is-invalid');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = message;
        
        input.parentNode.appendChild(errorElement);
    }

    clearValidationMessage(input) {
        const errorElement = input.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }

    // Notifications
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('#notification-container');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} animate-slide-down`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button type="button" class="btn btn-sm btn-outline" onclick="this.parentElement.parentElement.remove()">
                    ×
                </button>
            </div>
        `;
        
        container.appendChild(notification);
        
        if (duration > 0) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, duration);
        }
    }

    hideNotification(notification) {
        notification.classList.add('animate-fade-out');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Modals
    showModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('animate-fade-in');
        document.body.classList.add('overflow-hidden');
    }

    hideModal(modal) {
        modal.classList.add('animate-fade-out');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('animate-fade-in', 'animate-fade-out');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }

    // Alerts
    hideAlert(alert) {
        alert.classList.add('animate-fade-out');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }

    // Loading States
    initLoadingStates() {
        document.querySelectorAll('[data-loading]').forEach(element => {
            element.addEventListener('click', (e) => {
                this.setLoading(element, true);
            });
        });
    }

    setLoading(element, isLoading) {
        if (isLoading) {
            element.classList.add('loading');
            element.disabled = true;
        } else {
            element.classList.remove('loading');
            element.disabled = false;
        }
    }

    // AJAX Helper
    async ajax(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('AJAX Error:', error);
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    // Form Submission Helper
    async submitForm(form, options = {}) {
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        const method = form.method || 'POST';
        
        this.setLoading(form.querySelector('[type="submit"]'), true);
        
        try {
            const response = await this.ajax(url, {
                method: method,
                body: formData
            });
            
            if (response.success) {
                this.showNotification(response.message || 'Success!', 'success');
                
                if (options.onSuccess) {
                    options.onSuccess(response);
                }
                
                if (options.redirect) {
                    window.location.href = options.redirect;
                }
            } else {
                this.showNotification(response.message || 'An error occurred', 'error');
                
                if (response.errors) {
                    this.displayFormErrors(form, response.errors);
                }
            }
        } catch (error) {
            this.showNotification('An error occurred while processing your request', 'error');
        } finally {
            this.setLoading(form.querySelector('[type="submit"]'), false);
        }
    }

    displayFormErrors(form, errors) {
        Object.keys(errors).forEach(fieldName => {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                this.showValidationError(input, errors[fieldName][0]);
            }
        });
    }

    // Animations
    initAnimations() {
        // Intersection Observer for scroll animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('[data-animate]').forEach(element => {
            observer.observe(element);
        });
    }

    // Tooltips
    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.getAttribute('data-tooltip'));
            });
            
            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    showTooltip(element, text) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.style.cssText = `
            position: absolute;
            background: var(--gray-800);
            color: white;
            padding: var(--space-2);
            border-radius: var(--radius-md);
            font-size: var(--font-size-xs);
            z-index: 1000;
            pointer-events: none;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
    }

    hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    // Utility Methods
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.modernUI = new ModernUI();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernUI;
}
