/**
 * Form Component
 * Form generation, validation, and handling utilities
 * 
 * @package AnimalShelter
 */

const Form = {
    /**
     * Generate form HTML from field definitions
     * @param {Array} fields - Field definitions
     * @param {Object} values - Initial values
     * @returns {string}
     */
    generate(fields, values = {}) {
        return fields.map(field => this.renderField(field, values[field.name])).join('');
    },

    /**
     * Render single field
     * @param {Object} field
     * @param {*} value
     * @returns {string}
     */
    renderField(field, value = '') {
        const {
            type = 'text',
            name,
            label,
            placeholder = '',
            required = false,
            disabled = false,
            readonly = false,
            options = [],
            hint = '',
            className = '',
            rows = 4,
            min,
            max,
            step,
            pattern,
            accept,
            multiple = false
        } = field;

        const requiredAttr = required ? 'required' : '';
        const disabledAttr = disabled ? 'disabled' : '';
        const readonlyAttr = readonly ? 'readonly' : '';
        const requiredClass = required ? 'required' : '';

        let inputHtml = '';

        switch (type) {
            case 'text':
            case 'email':
            case 'password':
            case 'number':
            case 'tel':
            case 'url':
            case 'date':
            case 'datetime-local':
            case 'time':
                inputHtml = `
                    <input 
                        type="${type}" 
                        id="${name}" 
                        name="${name}" 
                        class="form-input ${className}"
                        placeholder="${placeholder}"
                        value="${value || ''}"
                        ${requiredAttr}
                        ${disabledAttr}
                        ${readonlyAttr}
                        ${min !== undefined ? `min="${min}"` : ''}
                        ${max !== undefined ? `max="${max}"` : ''}
                        ${step !== undefined ? `step="${step}"` : ''}
                        ${pattern ? `pattern="${pattern}"` : ''}
                    >
                `;
                break;

            case 'textarea':
                inputHtml = `
                    <textarea 
                        id="${name}" 
                        name="${name}" 
                        class="form-textarea ${className}"
                        placeholder="${placeholder}"
                        rows="${rows}"
                        ${requiredAttr}
                        ${disabledAttr}
                        ${readonlyAttr}
                    >${value || ''}</textarea>
                `;
                break;

            case 'select':
                const optionsHtml = options.map(opt => {
                    const optValue = typeof opt === 'object' ? opt.value : opt;
                    const optLabel = typeof opt === 'object' ? opt.label : opt;
                    const selected = optValue == value ? 'selected' : '';
                    return `<option value="${optValue}" ${selected}>${optLabel}</option>`;
                }).join('');

                inputHtml = `
                    <select 
                        id="${name}" 
                        name="${name}" 
                        class="form-select ${className}"
                        ${requiredAttr}
                        ${disabledAttr}
                        ${multiple ? 'multiple' : ''}
                    >
                        ${placeholder ? `<option value="">${placeholder}</option>` : ''}
                        ${optionsHtml}
                    </select>
                `;
                break;

            case 'checkbox':
                inputHtml = `
                    <label class="form-checkbox">
                        <input 
                            type="checkbox" 
                            id="${name}" 
                            name="${name}" 
                            ${value ? 'checked' : ''}
                            ${disabledAttr}
                        >
                        <span>${label}</span>
                    </label>
                `;
                // Don't render label again for checkbox
                return `<div class="form-group">${inputHtml}${hint ? `<p class="form-hint">${hint}</p>` : ''}</div>`;

            case 'radio':
                inputHtml = options.map(opt => {
                    const optValue = typeof opt === 'object' ? opt.value : opt;
                    const optLabel = typeof opt === 'object' ? opt.label : opt;
                    const checked = optValue == value ? 'checked' : '';
                    return `
                        <label class="form-radio">
                            <input 
                                type="radio" 
                                name="${name}" 
                                value="${optValue}" 
                                ${checked}
                                ${disabledAttr}
                            >
                            <span>${optLabel}</span>
                        </label>
                    `;
                }).join('');
                break;

            case 'toggle':
                inputHtml = `
                    <label class="toggle">
                        <input 
                            type="checkbox" 
                            id="${name}" 
                            name="${name}" 
                            ${value ? 'checked' : ''}
                            ${disabledAttr}
                        >
                        <span class="toggle-slider"></span>
                    </label>
                `;
                break;

            case 'file':
                inputHtml = `
                    <input 
                        type="file" 
                        id="${name}" 
                        name="${name}" 
                        class="form-input ${className}"
                        ${accept ? `accept="${accept}"` : ''}
                        ${multiple ? 'multiple' : ''}
                        ${requiredAttr}
                        ${disabledAttr}
                    >
                `;
                break;

            case 'hidden':
                return `<input type="hidden" id="${name}" name="${name}" value="${value || ''}">`;

            case 'divider':
                return `<hr style="margin: var(--space-6) 0; border: none; border-top: 1px solid var(--border-color);">`;

            case 'heading':
                return `<h4 style="margin-top: var(--space-6); margin-bottom: var(--space-4);">${label}</h4>`;

            default:
                inputHtml = `
                    <input 
                        type="text" 
                        id="${name}" 
                        name="${name}" 
                        class="form-input ${className}"
                        placeholder="${placeholder}"
                        value="${value || ''}"
                        ${requiredAttr}
                        ${disabledAttr}
                        ${readonlyAttr}
                    >
                `;
        }

        return `
            <div class="form-group">
                ${label && type !== 'checkbox' ? `<label class="form-label ${requiredClass}" for="${name}">${label}</label>` : ''}
                ${inputHtml}
                ${hint ? `<p class="form-hint">${hint}</p>` : ''}
            </div>
        `;
    },

    /**
     * Get form data as object
     * @param {HTMLFormElement|string} form - Form element or selector
     * @returns {Object}
     */
    getData(form) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }

        if (!form) return {};

        const formData = new FormData(form);
        const data = {};

        for (const [key, value] of formData.entries()) {
            // Handle multiple values (checkboxes, multi-select)
            if (data.hasOwnProperty(key)) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        }

        // Handle unchecked checkboxes
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!checkbox.checked && !data.hasOwnProperty(checkbox.name)) {
                data[checkbox.name] = false;
            } else if (checkbox.checked && data[checkbox.name] === 'on') {
                data[checkbox.name] = true;
            }
        });

        return data;
    },

    /**
     * Set form data from object
     * @param {HTMLFormElement|string} form
     * @param {Object} data
     */
    setData(form, data) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }

        if (!form || !data) return;

        Object.entries(data).forEach(([name, value]) => {
            const field = form.elements[name];

            if (!field) return;

            if (field.type === 'checkbox') {
                field.checked = !!value;
            } else if (field.type === 'radio') {
                const radio = form.querySelector(`input[name="${name}"][value="${value}"]`);
                if (radio) radio.checked = true;
            } else if (field.tagName === 'SELECT' && field.multiple) {
                Array.from(field.options).forEach(opt => {
                    opt.selected = Array.isArray(value)
                        ? value.includes(opt.value)
                        : opt.value === value;
                });
            } else {
                field.value = value ?? '';
            }
        });
    },

    /**
     * Validate form
     * @param {HTMLFormElement|string} form
     * @param {Object} rules - Validation rules
     * @returns {boolean}
     */
    validate(form, rules = {}) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }

        if (!form) return false;

        // Clear previous errors
        this.clearErrors(form);

        // Use native validation first
        if (!form.checkValidity()) {
            form.reportValidity();
            if (window.Toast) window.Toast.warning('Please fill in all required fields');
            return false;
        }

        // Custom validation
        const data = this.getData(form);
        let isValid = true;

        Object.entries(rules).forEach(([fieldName, fieldRules]) => {
            const value = data[fieldName];
            const field = form.elements[fieldName];

            if (!field) return;

            const ruleList = fieldRules.split('|');

            for (const rule of ruleList) {
                const [ruleName, ruleParam] = rule.split(':');
                const error = this.checkRule(ruleName, value, ruleParam, data);

                if (error) {
                    this.showError(field, error);
                    isValid = false;
                    break;
                }
            }
        });

        if (!isValid && window.Toast) {
            window.Toast.warning('Please fix the errors in the form');
        }

        return isValid;
    },

    /**
     * Check single validation rule
     * @param {string} rule
     * @param {*} value
     * @param {*} param
     * @param {Object} data
     * @returns {string|null}
     */
    checkRule(rule, value, param, data) {
        switch (rule) {
            case 'required':
                if (!value || (typeof value === 'string' && !value.trim())) {
                    return 'This field is required';
                }
                break;

            case 'email':
                if (value && !Utils.isValidEmail(value)) {
                    return 'Please enter a valid email address';
                }
                break;

            case 'phone':
                if (value && !Utils.isValidPhone(value)) {
                    return 'Please enter a valid phone number';
                }
                break;

            case 'min':
                if (value && value.length < parseInt(param)) {
                    return `Must be at least ${param} characters`;
                }
                break;

            case 'max':
                if (value && value.length > parseInt(param)) {
                    return `Must be no more than ${param} characters`;
                }
                break;

            case 'minValue':
                if (value && parseFloat(value) < parseFloat(param)) {
                    return `Must be at least ${param}`;
                }
                break;

            case 'maxValue':
                if (value && parseFloat(value) > parseFloat(param)) {
                    return `Must be no more than ${param}`;
                }
                break;

            case 'match':
                if (value !== data[param]) {
                    return 'Fields do not match';
                }
                break;

            case 'pattern':
                if (value && !new RegExp(param).test(value)) {
                    return 'Invalid format';
                }
                break;
        }

        return null;
    },

    /**
     * Show field error
     * @param {HTMLElement} field
     * @param {string} message
     */
    showError(field, message) {
        field.classList.add('error');

        const formGroup = field.closest('.form-group');
        if (formGroup) {
            const errorEl = document.createElement('p');
            errorEl.className = 'form-error';
            errorEl.textContent = message;
            formGroup.appendChild(errorEl);
        }
    },

    /**
     * Clear all errors
     * @param {HTMLFormElement} form
     */
    clearErrors(form) {
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        form.querySelectorAll('.form-error').forEach(el => el.remove());
    },

    /**
     * Reset form
     * @param {HTMLFormElement|string} form
     */
    reset(form) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }

        if (form) {
            form.reset();
            this.clearErrors(form);
        }
    },

    /**
     * Serialize form data to URL params
     * @param {HTMLFormElement|string} form
     * @returns {string}
     */
    serialize(form) {
        const data = this.getData(form);
        return new URLSearchParams(data).toString();
    },

    /**
     * Handle form submission
     * @param {HTMLFormElement|string} form
     * @param {Function} onSubmit
     * @param {Object} options
     */
    handleSubmit(form, onSubmit, options = {}) {
        if (typeof form === 'string') {
            form = document.querySelector(form);
        }

        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const { rules = {}, resetOnSuccess = false } = options;

            // Validate
            if (!this.validate(form, rules)) {
                return;
            }

            // Get submit button
            const submitBtn = form.querySelector('[type="submit"]');

            try {
                // Set loading state
                if (submitBtn) {
                    Loading.setButtonLoading(submitBtn, true, options.loadingText);
                }

                // Get data and submit
                const data = this.getData(form);
                await onSubmit(data, form);

                // Reset if needed
                if (resetOnSuccess) {
                    this.reset(form);
                }
            } catch (error) {
                console.error('Form submission error:', error);
                Toast.error(error.message || 'An error occurred');
            } finally {
                // Reset loading state
                if (submitBtn) {
                    Loading.setButtonLoading(submitBtn, false);
                }
            }
        });
    },

    /**
     * Create inline edit field
     * @param {Object} options
     * @returns {string}
     */
    inlineEdit(options) {
        const { name, value, type = 'text', onSave } = options;

        return `
            <div class="inline-edit" data-name="${name}">
                <span class="inline-edit-value">${value}</span>
                <button class="btn-icon btn-ghost btn-sm inline-edit-btn" onclick="Form.startInlineEdit(this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
                <div class="inline-edit-form hidden">
                    <input type="${type}" class="form-input" value="${value}">
                    <button class="btn btn-sm btn-primary" onclick="Form.saveInlineEdit(this, '${name}')">Save</button>
                    <button class="btn btn-sm btn-ghost" onclick="Form.cancelInlineEdit(this)">Cancel</button>
                </div>
            </div>
        `;
    },

    /**
     * Start inline editing
     * @param {HTMLElement} button
     */
    startInlineEdit(button) {
        const container = button.closest('.inline-edit');
        container.querySelector('.inline-edit-value').classList.add('hidden');
        container.querySelector('.inline-edit-btn').classList.add('hidden');
        container.querySelector('.inline-edit-form').classList.remove('hidden');
        container.querySelector('input').focus();
    },

    /**
     * Save inline edit
     * @param {HTMLElement} button
     * @param {string} name
     */
    saveInlineEdit(button, name) {
        const container = button.closest('.inline-edit');
        const input = container.querySelector('input');
        const valueEl = container.querySelector('.inline-edit-value');

        valueEl.textContent = input.value;

        // Trigger custom event
        container.dispatchEvent(new CustomEvent('save', {
            detail: { name, value: input.value }
        }));

        this.cancelInlineEdit(button);
    },

    /**
     * Cancel inline edit
     * @param {HTMLElement} button
     */
    cancelInlineEdit(button) {
        const container = button.closest('.inline-edit');
        container.querySelector('.inline-edit-value').classList.remove('hidden');
        container.querySelector('.inline-edit-btn').classList.remove('hidden');
        container.querySelector('.inline-edit-form').classList.add('hidden');
    }
};

// Add form-specific styles
const formStyles = document.createElement('style');
formStyles.textContent = `
    .inline-edit {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }
    
    .inline-edit-form {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }
    
    .inline-edit-form input {
        width: auto;
        min-width: 150px;
    }
`;

document.head.appendChild(formStyles);

// Make Form globally available
window.Form = Form;