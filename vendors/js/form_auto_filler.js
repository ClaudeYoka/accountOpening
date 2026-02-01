/**
 * Form Auto-Fill Handler - Flexcube Integration
 * 
 * Automatically fills form fields with data from Flexcube API
 * Supports multiple field name variations and proper data formatting
 */

class FormAutoFiller {
    
    /**
     * Field mapping configuration
     * Maps form field IDs/names to possible field names from API
     */
    static fieldMappings = {
        'first-name': [
            'first-name', 'first_name', 'firstName', 
            'prenom', 'noms', 'given-name'
        ],
        'last-name': [
            'last-name', 'last_name', 'lastName', 
            'nom', 'family-name', 'surname'
        ],
        'middle-name': [
            'middle-name', 'middle_name', 'middleName', 
            'prenom2', 'second_name'
        ],
        'email': [
            'email', 'email_address', 'adresse_email', 
            'mail', 'email-address', 'emailID', 'email_id'
        ],
        'telephone': [
            'telephone', 'phone', 'tel', 'phone_number', 
            'numero_telephone', 'mobile', 'telephone1',
            'phoneNo', 'phone_no', 'phoneno'
        ],
        'telephone2': [
            'telephone2', 'phone2', 'tel2', 'phone_number_2', 
            'numero_telephone2', 'secondary_phone'
        ],
        'sex': [
            'sex', 'sexe', 'gender', 'genre'
        ],
        'nationality': [
            'nationality', 'nationalite', 'citizenship', 
            'pays_origine'
        ],
        'residence-country': [
            'residence-country', 'residence_country', 'country', 'pays', 
            'residence_pays', 'pays_residence'
        ],
        'country': [
            'country', 'pays', 'nation'
        ],
        'contact_adress': [
            'address', 'adresse', 'residential-address', 'contact_adress',
            'contact_address', 'lieu_residence', 'rue', 'street'
        ],
        'city': [
            'city', 'ville', 'town'
        ],
        'postal-code': [
            'postal-code', 'postal_code', 'zip-code', 
            'zip_code', 'code-postal'
        ],
        'date-of-birth': [
            'date-of-birth', 'date_of_birth', 'dob', 
            'dateNaissance', 'date_naissance'
        ],
        'employer-name': [
            'employer', 'employer-name', 'employer_name', 
            'employeur', 'company'
        ],
        'occupation': [
            'occupation', 'profession', 'job', 'metier'
        ],
        'title': [
            'title', 'civility', 'civility_id', 'titre'
        ],
        'form-bank-account-number': [
            'account_number', 'account-number', 'accountNumber', 
            'numero_compte', 'account_num'
        ],
        'customer-id': [
            'customer_id', 'customer-id', 'customerId', 
            'client_id', 'user_id'
        ],
        'branch-code': [
            'branch_code', 'branch-code', 'branchCode', 
            'code_agence', 'branch_num'
        ],
        'document-number': [
            'document_number', 'document-number', 'documentNumber',
            'id_num', 'id_number', 'numero_document'
        ],
        'BP': [
            'bp', 'BP', 'boite_postale', 'postal_box'
        ]
    };
    
    /**
     * Fills a form field with appropriate value
     * 
     * @param {string} fieldId - Form field ID
     * @param {*} value - Value to fill
     * @param {string} dataSource - Source field name from API (for reference)
     */
    static fillField(fieldId, value, dataSource = '') {
        if (!value) return;
        
        const element = document.getElementById(fieldId);
        if (!element) return;
        
        // Handle different element types
        const tagName = element.tagName.toLowerCase();
        const type = (element.type || '').toLowerCase();
        
        if (tagName === 'select') {
            // For select elements, try exact match then loose match
            element.value = value;
            
            // If no match, try to find by text content
            if (!element.value && element.querySelectorAll) {
                const options = element.querySelectorAll('option');
                for (let opt of options) {
                    if (opt.textContent.trim().toUpperCase() === String(value).trim().toUpperCase()) {
                        element.value = opt.value;
                        break;
                    }
                }
            }
        } else if (tagName === 'textarea') {
            element.textContent = value;
            element.value = value;
        } else if (type === 'checkbox' || type === 'radio') {
            // For checkboxes/radios, check if the value matches
            if (String(element.value).toLowerCase() === String(value).toLowerCase()) {
                element.checked = true;
            }
        } else if (tagName === 'input') {
            // For date inputs, ensure proper formatting
            if (type === 'date') {
                element.value = FormAutoFiller.formatDate(value, 'YYYY-MM-DD');
            } else {
                element.value = value;
            }
        } else {
            element.value = value;
        }
        
        // Trigger change event to update any dependent fields
        const event = new Event('change', { bubbles: true });
        element.dispatchEvent(event);
    }
    
    /**
     * Finds the most likely field ID for a given API field
     * 
     * @param {string} apiFieldName - Field name from API response
     * @returns {string|null} Field ID to fill or null
     */
    static findMatchingFieldId(apiFieldName) {
        const normalized = apiFieldName.toLowerCase().replace(/[_-]/g, '-');
        
        // Check direct mappings
        for (const [fieldId, aliases] of Object.entries(FormAutoFiller.fieldMappings)) {
            if (aliases.some(alias => alias.toLowerCase() === normalized)) {
                return fieldId;
            }
        }
        
        // Check partial matches
        for (const [fieldId, aliases] of Object.entries(FormAutoFiller.fieldMappings)) {
            if (aliases.some(alias => 
                normalized.includes(alias.toLowerCase()) || 
                alias.toLowerCase().includes(normalized)
            )) {
                return fieldId;
            }
        }
        
        return null;
    }
    
    /**
     * Auto-fills form from Flexcube data
     * 
     * @param {object} data - Flexcube response data
     * @param {object} options - Options {form: FormElement, debug: bool}
     */
    static autoFillForm(data, options = {}) {
        const form = options.form || document.querySelector('form');
        const debug = options.debug || false;
        
        if (!form) {
            console.error('FormAutoFiller: No form found');
            return false;
        }
        
        if (debug) {
            console.log('FormAutoFiller: Starting auto-fill', data);
        }
        
        let filledCount = 0;
        const filled = {};
        
        // Process form_fields if available (mapped UDF data)
        if (data.form_fields && typeof data.form_fields === 'object') {
            for (const [apiField, value] of Object.entries(data.form_fields)) {
                const fieldId = FormAutoFiller.findMatchingFieldId(apiField);
                
                if (fieldId) {
                    FormAutoFiller.fillField(fieldId, value, apiField);
                    filled[fieldId] = {value, source: apiField};
                    filledCount++;
                    
                    if (debug) {
                        console.log(`  ✓ ${fieldId} = ${value} (from ${apiField})`);
                    }
                }
            }
        }
        
        // Also try to fill from any other available fields
        for (const [key, value] of Object.entries(data)) {
            if (key === 'form_fields' || key === 'raw_response' || 
                key === 'raw' || key === 'udf_raw' || typeof value !== 'string') {
                continue;
            }
            
            if (filled[key]) continue; // Already filled
            
            const fieldId = FormAutoFiller.findMatchingFieldId(key);
            if (fieldId && !filled[fieldId]) {
                FormAutoFiller.fillField(fieldId, value, key);
                filled[fieldId] = {value, source: key};
                filledCount++;
                
                if (debug) {
                    console.log(`  ✓ ${fieldId} = ${value} (from ${key})`);
                }
            }
        }
        
        if (debug) {
            console.log(`FormAutoFiller: Filled ${filledCount} fields`);
        }
        
        return true;
    }
    
    /**
     * Formats a date value to target format
     * 
     * @param {string} value - Date value (various formats)
     * @param {string} format - Target format (YYYY-MM-DD, DD/MM/YYYY, etc)
     * @returns {string} Formatted date
     */
    static formatDate(value, format = 'YYYY-MM-DD') {
        if (!value) return '';
        
        // Try to parse various date formats
        let date;
        
        // ISO format (2024-01-20 or 2024-01-20T10:00:00)
        if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
            date = new Date(value);
        }
        // DD/MM/YYYY
        else if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) {
            const parts = value.split('/');
            date = new Date(parts[2], parts[1] - 1, parts[0]);
        }
        // MM/DD/YYYY
        else if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(value)) {
            date = new Date(value);
        }
        // DD-MM-YYYY
        else if (/^\d{2}-\d{2}-\d{4}$/.test(value)) {
            const parts = value.split('-');
            date = new Date(parts[2], parts[1] - 1, parts[0]);
        }
        
        if (!date || isNaN(date.getTime())) {
            return value; // Return as-is if can't parse
        }
        
        // Format output
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        if (format === 'DD/MM/YYYY') {
            return `${day}/${month}/${year}`;
        } else if (format === 'MM/DD/YYYY') {
            return `${month}/${day}/${year}`;
        } else {
            // Default YYYY-MM-DD
            return `${year}-${month}-${day}`;
        }
    }
    
    /**
     * Validates if a field is already filled
     * 
     * @param {string} fieldId - Field ID
     * @returns {boolean}
     */
    static isFieldFilled(fieldId) {
        const element = document.getElementById(fieldId);
        if (!element) return false;
        
        return element.value && element.value.trim().length > 0;
    }
    
    /**
     * Clears all form fields
     * 
     * @param {FormElement} form - Form element
     */
    static clearForm(form) {
        if (!form) form = document.querySelector('form');
        if (!form) return;
        
        form.querySelectorAll('input, select, textarea').forEach(el => {
            const type = (el.type || '').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                el.checked = false;
            } else {
                el.value = '';
            }
        });
    }
}

// Export for use in browser or Node
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormAutoFiller;
}
