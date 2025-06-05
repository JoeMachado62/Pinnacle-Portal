// config/dealertrack.js
module.exports = {
  baseUrl: 'https://ww2.dealertrack.app.coxautoinc.com', // Base URL for DealerTrack
  paths: {
    fniHome: '/core/deals_or_apps/', // Typical F&I landing page
    newAppStart: '/customer/search/', // Starting point for a new application (customer search)
    appStatusSearch: '/decisions/credit/status/', // Page to search for application/deal statuses
    dealList: '/dealjackets/', // Or a more specific path to the active deals list
    // Add specific deal jacket path if templateable, e.g., /dealjackets/{DEAL_JACKET_ID}/deals/active/
  },
  selectors: {
    // --- General Navigation ---
    fniHomeLink: 'a[href="/core/deals_or_apps/"]', // Example, adjust if needed
    creditAppQuickLink: 'a[href^="/customer/search/"]', // From existing code
    activeDealsLink: 'a[href^="/core/fni20"]', // From existing code for viewing existing deals

    // --- Customer Search Page (New App Start) ---
    customerSearch: {
      // Selectors from your prompt, may need adjustment based on actual page
      firstNameInput: '#id_first_name-maskedInput', // Or general: '#id_applicant_form-first_name' from old code
      lastNameInput: '#id_last_name-maskedInput',   // Or general: '#id_applicant_form-last_name' from old code
      ssnInput: '#id_tax_id-maskedInput', // Or general: '#id_applicant_form-tax_id' from old code
      individualRadio: '#deal-creator-name-section .cx-radios label:nth-child(1)', // More specific if needed
      businessRadio: '#deal-creator-name-section .cx-radios label:nth-child(2)', // More specific if needed
      businessNameInput: '#id_business_name-maskedInput',
      searchButton: 'button[type=submit]', // General, might need specific ID like '#customer_search_submit_btn'
      possibleDuplicatesTable: 'table#possible_duplicates_table', // Example
      possibleDuplicatesRows: 'table#possible_duplicates_table tbody tr',
      continueWithNewButton: 'button#continue_with_new_application_btn', // Example
      selectExistingCustomerLink: 'a.select_existing_customer', // Example
    },
    // --- Application Preferences (Screen One of New App Wizard) ---
    appPrefs: {
      assetTypeAutoRadio: '#id_applicant_form-asset_type_1',
      vehicleConditionNewRadio: '#id_vehicle_form-condition_type_1',
      vehicleConditionUsedRadio: '#id_vehicle_form-condition_type_2',
      hasCoapplicantYesRadio: '#id_applicant_form-has_coapplicant_1',
      hasCoapplicantNoRadio: '#id_applicant_form-has_coapplicant_2',
      productTypeRetailRadio: '#id_vehicle_form-product_type_1',
      nextButton: '#btn_app_pref_submit a.btn-primary', // Or general 'button[type="submit"].btn-primary'
    },
    // --- Application Wizard (Applicant, Co-App, Vehicle, Finance sections) ---
    // Group selectors by section for clarity
    applicantForm: {
      firstName: '#id_applicant_form-first_name',
      lastName: '#id_applicant_form-last_name',
      middleName: '#id_applicant_form-middle_name',
      suffixCode: '#id_applicant_form-suffix_code',
      taxId: '#id_applicant_form-tax_id', // SSN
      birthDate: '#id_applicant_form-birth_date',
      addressLine1: '#id_applicant_form-line_1_address',
      addressLine2: '#id_applicant_form-line_2_address',
      zipCode: '#id_applicant_form-zip_code',
      cityStateDropdown: '#id_applicant_form-city_state_dropdown', // If city/state is a dropdown post-zip
      city: '#id_applicant_form-city', // If direct input fields
      state: '#id_applicant_form-state_code', // If direct input fields/dropdown
      primaryPhoneNumber: '#id_applicant_form-primary_phone_number',
      alternatePhoneNumber: '#id_applicant_form-alternate_phone_number', // Cell
      emailAddress: '#id_applicant_form-email_address',
      emailNotProvidedCheckbox: '#id_applicant_form-email_not_provided',
      driversLicenseStateCode: '#id_applicant_form-drivers_license_us_state_code',
      driversLicenseNumber: '#id_applicant_form-drivers_license',
      maritalStatusCode: '#id_applicant_form-marital_status_code',
      housingStatusCode: '#id_applicant_form-housing_status_code',
      currentAddressYears: '#id_applicant_form-current_address_years',
      currentAddressMonths: '#id_applicant_form-current_address_months',
      monthlyHousingPayment: '#id_applicant_form-mortgage_payment_or_rent',
      // ... prev address fields ...
      employmentStatusCode: '#id_applicant_form-employment_status_code',
      employerName: '#id_applicant_form-organization_name',
      employedYears: '#id_applicant_form-current_employed_years',
      employedMonths: '#id_applicant_form-current_employed_months',
      workPhoneNumber: '#id_applicant_form-work_phone_number',
      occupationName: '#id_applicant_form-occupation_name',
      salary: '#id_applicant_form-salary',
      salaryTypeCode: '#id_applicant_form-salary_type_code', // Pay frequency
      // ... prev employment fields ...
    },
    coApplicantForm: { // Assuming similar IDs with 'co_applicant_form' prefix
      partyRelationshipCode: '#id_co_applicant_form-party_relationship_code',
      firstName: '#id_co_applicant_form-first_name',
      lastName: '#id_co_applicant_form-last_name',
      taxId: '#id_co_applicant_form-tax_id',
      birthDate: '#id_co_applicant_form-birth_date',
      sameAddressCheckbox: '#id_co_applicant_form-same_address',
      addressLine1: '#id_co_applicant_form-line_1_address',
      zipCode: '#id_co_applicant_form-zip_code',
      housingStatusCode: '#id_co_applicant_form-housing_status_code',
      currentAddressYears: '#id_co_applicant_form-current_address_years',
      currentAddressMonths: '#id_co_applicant_form-current_address_months',
      // ... more co-applicant fields ...
    },
    vehicleForm: {
      yearDropdown: '#id_vehicle_form-year_id',
      makeDropdown: '#id_vehicle_form-make_id',
      modelDropdown: '#id_vehicle_form-model_id',
      trimDropdown: '#id_vehicle_form-trim_id',
      toggleCustomVehicleButton: '#toggle_custom_vehicle',
      yearCustomInput: '#id_vehicle_form-year_custom',
      // ... other custom vehicle inputs ...
      paymentCallNoRadio: '#id_vehicle_form-payment_call_indicator_2',
      paymentCallYesRadio: '#id_vehicle_form-payment_call_indicator_1',
      stockNumberInput: '#id_vehicle_form-stock_number',
      vinInput: '#id_vehicle_form-vin_number',
      odometerInput: '#id_vehicle_form-odometer_number',
      sellingPriceInput: '#id_vehicle_form-cash_sell_price_amount',
      termInput: '#id_vehicle_form-term_count',
      salesTaxInput: '#id_vehicle_form-sales_tax_amount',
      titleLicenseFeeInput: '#id_vehicle_form-title_and_license_amount',
      cashDownInput: '#id_vehicle_form-cash_down_amount',
      docFeeInput: '#id_vehicle_form-front_end_fee_amount', // Map "Front End Fees" to Doc Fees
      rebateInput: '#id_vehicle_form-rebate_amount',
      netTradeInput: '#id_vehicle_form-trade_in_value_amount',
      // ... other financial inputs ...
      disclosureIndividualRadio: '#id_vehicle_form-disclosures_2', // Individual credit disclosure
      disclosureJointRadio: '#id_vehicle_form-disclosures_1',    // Reg B Joint credit disclosure
      commentsInput: '#id_vehicle_form-comments',
      submitApplicationButton: 'button[type="submit"].btn-primary', // Final submit, make more specific if possible
    },
    // --- Post-Submission / Deal Jacket ---
    dealJacket: {
      header: '#djheader_deal', // Main deal jacket header
      // Selector for the DT Reference number / Deal ID (from your notes & further assumption)
      // This might be text like "Deal # XXXXX" or a specific element containing the number.
      referenceNumberSpan: '#djheader_deal span.deal-number-selector', // As per your notes
      // Fallback: '.row-fluid .span6:last-child' within #djheader_deal if the above is too generic
      // or specific elements that contain "Loan #", "Deal ID", etc.
      // Example: '.deal-info-value[data-testid="deal-id"]'
    },
    // --- App Status Search Page ---
    appStatusSearch: {
      searchInput: 'input[name="search"]',
      searchButton: 'button[type="submit"]', // General submit, may need specific ID
      resultsTable: 'table.results', // Table containing search results
      firstResultStatusColumn: 'table.results tbody tr:first-child td.status-column', // Status text of the first result
      firstResultDealLink: 'table.results tbody tr:first-child a.deal-link', // Link to the deal jacket from search result
    },
    // --- Deal List Page (Active Deals) ---
    dealList: {
      searchInput: '#search-text-box',
      resultsTable: '#search_results_table table',
      resultsTableRows: '#search_results_table table tbody tr',
      dateCellInRow: 'td:nth-child(3)', // Adjust if "Created" column index changes
      actionCellInRow: 'td:nth-child(5) div.cx-badge', // Adjust for "Active Deal" badge or link
      // Add selectors for specific columns if needed for precise deal identification
      // e.g., customerNameCellInRow: 'td:nth-child(X)'
    },
  },
  timeouts: {
    navigation: 60000, // Longer default for page loads, especially after submissions
    shortWait: 3000,   // For small dynamic UI updates
    mediumWait: 10000, // For waiting for selectors
    longWait: 30000,   // For operations that might take longer (e.g., search results)
  },
  // --- Mappings ---
  // Example: map your system's housing status to DealerTrack's expected values
  housingStatusMap: {
    own: 'O',  // Example: 'O' for Own
    rent: 'R', // Example: 'R' for Rent
    other: 'X', // Example: 'X' for Other
    // Add more from your specific form data if DealerTrack has more detailed options
  },
  employmentStatusMap: {
    employed: 'E',
    self_employed: 'S',
    unemployed: 'U',
    // ... add others
  },
  payFrequencyMap: {
    weekly: 'W',
    bi_weekly: 'B',
    semi_monthly: 'S',
    monthly: 'M',
    annually: 'A',
    // ... add others
  }
};