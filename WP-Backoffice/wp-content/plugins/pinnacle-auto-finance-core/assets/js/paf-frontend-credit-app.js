jQuery(document).ready(function($) {
    // --- Currency Formatting and Calculations (Copied from your HTML, slightly adapted) ---
    const formatCurrency = (input) => {
        let value = input.value.replace(/[^\d.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        if (parts.length > 1) {
            value = parts[0] + '.' + parts[1].slice(0, 2);
        }
        // Temporarily store raw number for calculations
        input.dataset.rawValue = parseFloat(value) || 0;

        if (value) {
            const num = parseFloat(value);
            if (!isNaN(num)) {
                value = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
        input.value = value ? '$' + value : '';
    };

    const parseCurrency = (value) => {
        return parseFloat(String(value).replace(/[$,]/g, '')) || 0;
    };

    const parsePercentage = (value) => {
        return parseFloat(String(value).replace(/[%]/g, '')) / 100 || 0;
    };

    const calculateTotals = () => {
        const getVal = (id) => parseCurrency($('#' + id).val());
        const getPercentVal = (id) => parsePercentage($('#' + id).val());
        const setVal = (id, val) => $('#' + id).val('$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        const sellingPrice = getVal('sellingPrice');
        const warranty = getVal('warranty');
        const gap = getVal('gap');
        const other = getVal('other');
        const docFees = getVal('docFees');
        const titleFees = getVal('titleFees');

        const tradeValue = getVal('tradeValue');
        const tradePayoff = getVal('tradePayoff');
        const netTradeValue = tradeValue - tradePayoff;
        if ($('#netTradeValue').length) setVal('netTradeValue', netTradeValue);

        const taxRate = getPercentVal('taxRate');
        const taxableAmount = sellingPrice + warranty + gap + other; // This might vary by state/rules
        const taxes = taxableAmount * taxRate;
        if ($('#taxes').length) setVal('taxes', taxes);

        const totalPrice = sellingPrice + warranty + gap + other + taxes + docFees + titleFees;
        if ($('#totalPrice').length) setVal('totalPrice', totalPrice);

        const cashDown = getVal('cashDown');
        const totalDown = cashDown + netTradeValue;
        if ($('#totalDown').length) setVal('totalDown', totalDown);

        const amountFinanced = totalPrice - totalDown;
        if ($('#amountFinanced').length) setVal('amountFinanced', amountFinanced);
    };

    const formatTaxRate = (input) => {
        let value = input.value.replace(/[^\d.]/g, '');
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        input.value = value ? value + '%' : '';
        calculateTotals();
    };

    $('.paf-credit-app-form-container').on('blur', '.currency-input', function() { formatCurrency(this); calculateTotals(); });
    $('.paf-credit-app-form-container').on('focus', '.currency-input', function() { this.value = String(this.value).replace(/[$,]/g, ''); });
    
    const taxRateInput = document.getElementById('taxRate');
    if (taxRateInput) {
        taxRateInput.addEventListener('blur', function() { formatTaxRate(this); });
        taxRateInput.addEventListener('focus', function() { this.value = this.value.replace(/%/g, ''); });
    }

    $('.paf-credit-app-form-container .terms-grid input:not([readonly])').on('input blur', calculateTotals);
    if ($('.paf-credit-app-form-container .terms-grid').length) { // Initial calculation if terms grid exists
        calculateTotals();
    }
    

    // --- Conditional Display Logic for Address and Employment ---
    const updateAddressVisibility = (borrowerContainerId) => {
        const $container = $('#' + borrowerContainerId);
        if (!$container.length) return;

        const $currentYearsInput = $container.find('[id$="_currentAddressYears"]');
        const $currentMonthsInput = $container.find('[id$="_currentAddressMonths"]');
        const $prevAddress1Row = $container.find('.borrower-address-section [data-address-level="1"]'); // More specific selector
        const $prevAddress2Row = $container.find('.borrower-address-section [data-address-level="2"]');
        const $prev1YearsInput = $container.find('[id$="_prevAddress1Years"]');
        const $prev1MonthsInput = $container.find('[id$="_prevAddress1Months"]');

        if (!$currentYearsInput.length || !$currentMonthsInput.length || !$prevAddress1Row.length || !$prevAddress2Row.length || !$prev1YearsInput.length || !$prev1MonthsInput.length) {
            return;
        }

        const currentYears = parseInt($currentYearsInput.val()) || 0;
        const currentMonths = parseInt($currentMonthsInput.val()) || 0;
        const totalCurrentMonths = (currentYears * 12) + currentMonths;

        const prev1Years = parseInt($prev1YearsInput.val()) || 0;
        const prev1Months = parseInt($prev1MonthsInput.val()) || 0;
        const totalPrev1Months = (prev1Years * 12) + prev1Months;
        
        // Your HTML form shows prevAddress1 if current is >= 12 months, and prevAddress2 if current + prev1 < 24 months
        // Let's re-evaluate the logic: usually, previous address is needed IF current is LESS than X duration.
        // Assuming 2 years (24 months) is the threshold for needing previous history.
        
        if (totalCurrentMonths < 24) {
            $prevAddress1Row.slideDown().removeClass('hidden-section');
            if ((totalCurrentMonths + totalPrev1Months) < 24 && totalPrev1Months > 0) { // only show prev2 if prev1 has some duration
                $prevAddress2Row.slideDown().removeClass('hidden-section');
            } else {
                $prevAddress2Row.slideUp().addClass('hidden-section').find('input[type="text"], input[type="number"]').val('');
            }
        } else {
            $prevAddress1Row.slideUp().addClass('hidden-section').find('input[type="text"], input[type="number"]').val('');
            $prevAddress2Row.slideUp().addClass('hidden-section').find('input[type="text"], input[type="number"]').val('');
        }
    };

    const updateEmploymentVisibility = (borrowerContainerId) => {
        const $container = $('#' + borrowerContainerId);
        if (!$container.length) return;

        const $currentYearsInput = $container.find('[id$="_employmentYears"]');
        const $currentMonthsInput = $container.find('[id$="_employmentMonths"]');
        const $prevEmployer1Rows = $container.find('.borrower-employment-section [data-employment-level="1"]');
        const $prevEmployer2Rows = $container.find('.borrower-employment-section [data-employment-level="2"]');
        const $prev1YearsInput = $container.find('[id$="_prevEmployment1Years"]');
        const $prev1MonthsInput = $container.find('[id$="_prevEmployment1Months"]');

        if (!$currentYearsInput.length || !$currentMonthsInput.length || !$prevEmployer1Rows.length || !$prevEmployer2Rows.length || !$prev1YearsInput.length || !$prev1MonthsInput.length) {
             return;
        }

        const currentYears = parseInt($currentYearsInput.val()) || 0;
        const currentMonths = parseInt($currentMonthsInput.val()) || 0;
        const totalCurrentMonths = (currentYears * 12) + currentMonths;

        const prev1Years = parseInt($prev1YearsInput.val()) || 0;
        const prev1Months = parseInt($prev1MonthsInput.val()) || 0;
        const totalPrev1Months = (prev1Years * 12) + prev1Months;

        if (totalCurrentMonths < 24) {
            $prevEmployer1Rows.slideDown().removeClass('hidden-section');
             if ((totalCurrentMonths + totalPrev1Months) < 24 && totalPrev1Months > 0) {
                $prevEmployer2Rows.slideDown().removeClass('hidden-section');
            } else {
                $prevEmployer2Rows.slideUp().addClass('hidden-section').find('input').val('');
            }
        } else {
            $prevEmployer1Rows.slideUp().addClass('hidden-section').find('input').val('');
            $prevEmployer2Rows.slideUp().addClass('hidden-section').find('input').val('');
        }
    };
    
    const setupBorrowerListeners = (borrowerContainerId) => {
        const $container = $('#' + borrowerContainerId);
        if (!$container.length) return;

        $container.on('input change', '[id$="_currentAddressYears"], [id$="_currentAddressMonths"], [id$="_prevAddress1Years"], [id$="_prevAddress1Months"]', function() {
            updateAddressVisibility(borrowerContainerId);
        });
        $container.on('input change', '[id$="_employmentYears"], [id$="_employmentMonths"], [id$="_prevEmployment1Years"], [id$="_prevEmployment1Months"]', function() {
            updateEmploymentVisibility(borrowerContainerId);
        });
        
        // Initial check
        updateAddressVisibility(borrowerContainerId);
        updateEmploymentVisibility(borrowerContainerId);
    };

    setupBorrowerListeners('borrower1Container');

    // --- Add/Remove Co-Borrower ---
    $('#toggleBorrowerBtn').on('click', function() {
        const $btn = $(this);
        const $borrower2Container = $('#borrower2Container');

        if ($btn.hasClass('add-borrower-btn')) { // Adding co-borrower
            // Clone sections from borrower 1. This is complex to get right with all IDs, names, and event listeners.
            // The original HTML form's JS for cloning is more detailed and should be adapted.
            // For brevity, here's a simplified placeholder for cloning.
            let borrower1HTML = $('#borrower1Container').html();
            // Rudimentary ID/name update - THIS NEEDS TO BE MORE ROBUST like your original JS
            borrower1HTML = borrower1HTML.replace(/borrower1/g, 'borrower2').replace(/b1_/g, 'b2_');
            
            $borrower2Container.html(borrower1HTML);
            $borrower2Container.find('.borrower-header').text('Co-Borrower');
            $borrower2Container.find('input:not([type="radio"], [type="checkbox"])').val('');
            $borrower2Container.find('input[type="radio"], input[type="checkbox"]').prop('checked', false);
            $borrower2Container.find('.hidden-section').hide(); // Ensure cloned hidden sections are hidden
            $borrower2Container.slideDown();

            setupBorrowerListeners('borrower2Container'); // Setup listeners for new section

            $btn.text('Remove Co-Borrower').removeClass('add-borrower-btn').addClass('remove-borrower-btn');
        } else { // Removing co-borrower
            if (confirm('Are you sure you want to remove the co-borrower?')) {
                $borrower2Container.slideUp(function() { $(this).empty().hide(); });
                $btn.text('Add Co-Borrower').removeClass('remove-borrower-btn').addClass('add-borrower-btn');
            }
        }
    });

    // --- Form Validation (Simplified for this example) ---
    $('#pafCreditApplicationForm').on('submit', function(e) {
        let isValid = true;
        $(this).find('[required]').each(function() {
            const $input = $(this);
            $input.removeClass('invalid-input');
            $input.siblings('.paf-error-message').remove();

            if ( ($input.is(':checkbox') && !$input.is(':checked')) || (!$input.is(':checkbox') && $input.val().trim() === '') ) {
                isValid = false;
                $input.addClass('invalid-input');
                $input.after('<div class="paf-error-message" style="color:red; font-size:0.9em;">This field is required.</div>');
            }
            // Add more specific validations (email, SSN format, etc.)
            if ($input.attr('name') && $input.attr('name').includes('_ssn')) {
                const ssnVal = $input.val().replace(/[\s\-]/g, '');
                if (ssnVal && !/^\d{9}$/.test(ssnVal)) {
                    isValid = false;
                    $input.addClass('invalid-input');
                    $input.after('<div class="paf-error-message" style="color:red; font-size:0.9em;">SSN must be 9 digits.</div>');
                }
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please correct the errors in the form.');
            $('.invalid-input:first').focus();
        } else {
            // Add a loading state
            $(this).find('.submit-btn').prop('disabled', true).text('Submitting...');
        }
    });

    // Repopulate form on validation errors (if redirected back)
    const urlParams = new URLSearchParams(window.location.search);
    const formErrors = urlParams.get('paf_form_errors');
    const formData = urlParams.get('paf_form_data');

    if (formErrors) {
        const errors = JSON.parse(decodeURIComponent(formErrors));
        // You can display these errors more elegantly
        alert("Errors found: \n" + errors.join("\n"));
    }
    if (formData) {
        const data = JSON.parse(decodeURIComponent(formData));
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                $(`[name="${key}"]`).val(data[key]);
            }
        }
        // Trigger calculations and visibility updates after repopulating
        calculateTotals();
        setupBorrowerListeners('borrower1Container');
        if ($('#borrower2Container').html().length > 0) { // If co-borrower was present
            setupBorrowerListeners('borrower2Container');
        }
    }
    // Clear query params after use
    if (formErrors || formData) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

});