/**
 * Role-based registration — student helpers + organization multi-step wizard
 */
(function () {
    'use strict';

    var TOTAL_STEPS = 4;

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function bindPasswordToggles(root) {
        qsa('[data-toggle-password]', root).forEach(function (toggle) {
            if (toggle.__ftpBound) return;
            toggle.__ftpBound = true;
            toggle.addEventListener('click', function () {
                var id = toggle.getAttribute('data-toggle-password');
                var field = document.getElementById(id);
                if (!field) return;
                if (field.type === 'password') {
                    field.type = 'text';
                    toggle.textContent = '👁️‍🗨️';
                } else {
                    field.type = 'password';
                    toggle.textContent = '👁️';
                }
            });
        });
    }

    function bindPasswordStrength(fieldId, barId) {
        var passwordField = document.getElementById(fieldId);
        var strengthBar = document.getElementById(barId);
        if (!passwordField || passwordField.__ftpStrengthBound) return;
        passwordField.__ftpStrengthBound = true;
        passwordField.addEventListener('input', function () {
            var password = passwordField.value || '';
            var hasLength = password.length >= 8;
            var hasUppercase = /[A-Z]/.test(password);
            var hasLowercase = /[a-z]/.test(password);
            var hasNumber = /\d/.test(password);
            var strength = 0;
            if (hasLength) strength++;
            if (hasUppercase) strength++;
            if (hasLowercase) strength++;
            if (hasNumber) strength++;

            if (!strengthBar) return;
            strengthBar.className = 'password-strength';
            if (password.length === 0) return;
            if (strength <= 2) strengthBar.className = 'password-strength weak';
            else if (strength === 3) strengthBar.className = 'password-strength medium';
            else strengthBar.className = 'password-strength strong';
        });
    }

    function bindStudentUniversityToggle() {
        var select = document.getElementById('student-university');
        var wrap = document.getElementById('student-university-other-wrap');
        if (!select || !wrap || select.__ftpBound) return;
        select.__ftpBound = true;

        function sync() {
            var isOther = select.value === 'Other (Please specify)';
            wrap.hidden = !isOther;
        }

        select.addEventListener('change', sync);
        sync();
    }

    function bindFormSubmitLoading(formId) {
        var form = document.getElementById(formId);
        if (!form || form.__ftpSubmitBound) return;
        form.__ftpSubmitBound = true;
        form.addEventListener('submit', function () {
            var btn = form.querySelector('[type="submit"]:not([hidden])');
            if (btn) {
                btn.classList.add('is-loading');
                btn.disabled = true;
            }
        });
    }

    function isEmpty(value) {
        return value === null || value === undefined || String(value).trim() === '';
    }

    function markInvalid(input, message) {
        input.classList.add('is-invalid');
        var group = input.closest('.auth-field-group') || input.closest('.mb-3') || input.closest('.form-check');
        if (!group) return;
        group.classList.add('has-error');
        var existing = group.querySelector('.auth-wizard-inline-error');
        if (!existing) {
            existing = document.createElement('div');
            existing.className = 'auth-wizard-inline-error invalid-feedback d-block';
            group.appendChild(existing);
        }
        existing.textContent = message || 'This field is required.';
    }

    function clearInvalid(input) {
        input.classList.remove('is-invalid');
        var group = input.closest('.auth-field-group') || input.closest('.mb-3') || input.closest('.form-check');
        if (!group) return;
        group.classList.remove('has-error');
        var existing = group.querySelector('.auth-wizard-inline-error');
        if (existing) existing.remove();
    }

    function validatePasswordPair(passwordInput, confirmInput) {
        var ok = true;
        var password = passwordInput.value || '';
        var pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;

        if (isEmpty(password)) {
            markInvalid(passwordInput, 'Password is required.');
            ok = false;
        } else if (password.length < 8) {
            markInvalid(passwordInput, 'Password must be at least 8 characters.');
            ok = false;
        } else if (!pattern.test(password)) {
            markInvalid(passwordInput, 'Include uppercase, lowercase, and a number.');
            ok = false;
        } else {
            clearInvalid(passwordInput);
        }

        if (isEmpty(confirmInput.value)) {
            markInvalid(confirmInput, 'Please confirm your password.');
            ok = false;
        } else if (confirmInput.value !== password) {
            markInvalid(confirmInput, 'Passwords do not match.');
            ok = false;
        } else {
            clearInvalid(confirmInput);
        }

        return ok;
    }

    function validatePanelStep(panel) {
        var ok = true;
        var step = parseInt(panel.getAttribute('data-wizard-panel'), 10);

        qsa('[data-wizard-required]', panel).forEach(function (input) {
            if (input.type === 'file') {
                if (!input.files || input.files.length === 0) {
                    markInvalid(input, 'Please upload a file.');
                    ok = false;
                } else {
                    clearInvalid(input);
                }
                return;
            }

            if (input.type === 'checkbox') {
                if (!input.checked) {
                    markInvalid(input, 'You must agree to continue.');
                    ok = false;
                } else {
                    clearInvalid(input);
                }
                return;
            }

            if (isEmpty(input.value)) {
                markInvalid(input, 'This field is required.');
                ok = false;
            } else {
                clearInvalid(input);
            }
        });

        if (step === 1) {
            var email = qs('[name="OrganizationSignupForm[email]"]', panel);
            if (email && !isEmpty(email.value) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                markInvalid(email, 'Enter a valid email address.');
                ok = false;
            }
            var pass = qs('#organizationsignupform-password', panel);
            var confirm = qs('#organizationsignupform-confirm_password', panel);
            if (pass && confirm && !validatePasswordPair(pass, confirm)) ok = false;
        }

        if (step === 2) {
            var website = qs('[name="OrganizationSignupForm[website]"]', panel);
            if (website && !isEmpty(website.value)) {
                try {
                    var url = website.value.indexOf('://') === -1 ? 'https://' + website.value : website.value;
                    // eslint-disable-next-line no-new
                    new URL(url);
                    clearInvalid(website);
                } catch (e) {
                    markInvalid(website, 'Enter a valid website URL.');
                    ok = false;
                }
            }
        }

        if (step === 4) {
            var terms = qs('[name="OrganizationSignupForm[terms]"]', panel);
            if (terms && !terms.checked) {
                markInvalid(terms, 'You must agree to the terms.');
                ok = false;
            }
        }

        return ok;
    }

    function updateWizardUI(wizardRoot, step) {
        var fill = qs('[data-wizard-progress-fill]', wizardRoot);
        var progress = qs('.auth-wizard-progress', wizardRoot);
        if (fill) fill.style.width = ((step / TOTAL_STEPS) * 100) + '%';
        if (progress) progress.setAttribute('aria-valuenow', String(step));

        qsa('[data-wizard-step-indicator]', wizardRoot).forEach(function (el) {
            var n = parseInt(el.getAttribute('data-wizard-step-indicator'), 10);
            el.classList.toggle('is-active', n === step);
            el.classList.toggle('is-complete', n < step);
        });

        qsa('[data-wizard-panel]', wizardRoot).forEach(function (panel) {
            var n = parseInt(panel.getAttribute('data-wizard-panel'), 10);
            var active = n === step;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });

        var prevBtn = qs('[data-wizard-prev]', wizardRoot);
        var nextBtn = qs('[data-wizard-next]', wizardRoot);
        var submitBtn = qs('[data-wizard-submit]', wizardRoot);
        if (prevBtn) prevBtn.hidden = step <= 1;
        if (nextBtn) nextBtn.hidden = step >= TOTAL_STEPS;
        if (submitBtn) submitBtn.hidden = step < TOTAL_STEPS;
    }

    function buildReviewList(wizardRoot) {
        var list = qs('.auth-review-list', wizardRoot);
        if (!list) return;

        var labels = {
            'OrganizationSignupForm[contact_person]': 'Contact Person',
            'OrganizationSignupForm[email]': 'Email',
            'OrganizationSignupForm[phone]': 'Phone',
            'OrganizationSignupForm[organization_name]': 'Organization',
            'OrganizationSignupForm[registration_number]': 'Registration Number',
            'OrganizationSignupForm[industry]': 'Industry',
            'OrganizationSignupForm[organization_type]': 'Type',
            'OrganizationSignupForm[country]': 'Country',
            'OrganizationSignupForm[region]': 'Region',
            'OrganizationSignupForm[city]': 'City',
            'OrganizationSignupForm[address]': 'Address',
            'OrganizationSignupForm[website]': 'Website'
        };

        list.innerHTML = '';
        Object.keys(labels).forEach(function (name) {
            var input = qs('[name="' + name + '"]', wizardRoot);
            if (!input) return;
            var value = input.tagName === 'SELECT'
                ? (input.options[input.selectedIndex] ? input.options[input.selectedIndex].text : '')
                : input.value;
            if (isEmpty(value) || value.indexOf('Select') === 0) return;

            var dt = document.createElement('dt');
            dt.textContent = labels[name];
            var dd = document.createElement('dd');
            dd.textContent = value;
            list.appendChild(dt);
            list.appendChild(dd);
        });

        var logoInput = qs('[data-org-logo-input]', wizardRoot);
        var certInput = qs('[name="OrganizationSignupForm[certificateFile]"]', wizardRoot);
        if (logoInput && logoInput.files && logoInput.files[0]) {
            var dtLogo = document.createElement('dt');
            dtLogo.textContent = 'Logo';
            var ddLogo = document.createElement('dd');
            ddLogo.textContent = logoInput.files[0].name;
            list.appendChild(dtLogo);
            list.appendChild(ddLogo);
        }
        if (certInput && certInput.files && certInput.files[0]) {
            var dtCert = document.createElement('dt');
            dtCert.textContent = 'Certificate';
            var ddCert = document.createElement('dd');
            ddCert.textContent = certInput.files[0].name;
            list.appendChild(dtCert);
            list.appendChild(ddCert);
        }
    }

    function detectErrorStep(wizardRoot) {
        var panels = qsa('[data-wizard-panel]', wizardRoot);
        var maxStep = 1;
        panels.forEach(function (panel) {
            var hasError = panel.querySelector('.invalid-feedback:not(.auth-wizard-inline-error), .is-invalid, .has-error');
            if (hasError) {
                var step = parseInt(panel.getAttribute('data-wizard-panel'), 10);
                if (step > maxStep) maxStep = step;
            }
        });
        return maxStep;
    }

    function bindOrgLogoPreview(wizardRoot) {
        var input = qs('[data-org-logo-input]', wizardRoot);
        var preview = qs('[data-org-logo-preview]', wizardRoot);
        if (!input || !preview || input.__ftpLogoBound) return;
        input.__ftpLogoBound = true;
        input.addEventListener('change', function () {
            preview.innerHTML = '';
            if (!input.files || !input.files[0]) {
                preview.hidden = true;
                return;
            }
            var file = input.files[0];
            if (!file.type.match(/^image\//)) {
                preview.hidden = true;
                return;
            }
            var img = document.createElement('img');
            img.alt = 'Logo preview';
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
            preview.hidden = false;
        });
    }

    function initOrganizationWizard() {
        var wizardRoot = qs('.auth-reg-step--organization');
        if (!wizardRoot || wizardRoot.__ftpWizardBound) return;
        wizardRoot.__ftpWizardBound = true;

        var currentStep = detectErrorStep(wizardRoot);
        updateWizardUI(wizardRoot, currentStep);
        bindOrgLogoPreview(wizardRoot);

        var prevBtn = qs('[data-wizard-prev]', wizardRoot);
        var nextBtn = qs('[data-wizard-next]', wizardRoot);

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardUI(wizardRoot, currentStep);
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                var panel = qs('[data-wizard-panel="' + currentStep + '"]', wizardRoot);
                if (!panel || !validatePanelStep(panel)) return;
                if (currentStep < TOTAL_STEPS) {
                    currentStep++;
                    if (currentStep === TOTAL_STEPS) buildReviewList(wizardRoot);
                    updateWizardUI(wizardRoot, currentStep);
                }
            });
        }
    }

    function initStudentForm() {
        bindStudentUniversityToggle();
        bindPasswordStrength('studentsignupform-password', 'password-strength');
        bindFormSubmitLoading('form-student-signup');
    }

    function init() {
        bindPasswordToggles(document);
        initStudentForm();
        initOrganizationWizard();
        bindPasswordStrength('organizationsignupform-password', null);
        bindFormSubmitLoading('form-organization-signup');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('ftp:auth-rebind', init);
})();
