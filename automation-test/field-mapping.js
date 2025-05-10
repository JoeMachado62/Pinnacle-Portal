/**
 * Field Mapping Configuration
 * 
 * This file defines the mapping between our WordPress Custom Post Type fields
 * and the corresponding DealerTrack form fields.
 * 
 * Format:
 * 'our_field_name': {
 *   selector: 'CSS selector for DealerTrack field',
 *   type: 'input|select|radio|checkbox', // Field type
 *   transform: (value) => { return transformedValue; }, // Optional transformation function
 *   required: true|false // Whether the field is required
 * }
 */

module.exports = {
  // Borrower Information
  'primary_borrower.first_name': {
    selector: 'input[name="firstName"], #firstName',
    type: 'input',
    required: true
  },
  'primary_borrower.last_name': {
    selector: 'input[name="lastName"], #lastName',
    type: 'input',
    required: true
  },
  'primary_borrower.ssn': {
    selector: 'input[name="ssn"], #ssn',
    type: 'input',
    transform: (value) => value.replace(/\D/g, ''), // Remove non-digits
    required: true
  },
  'primary_borrower.dob': {
    selector: 'input[name="dateOfBirth"], #dateOfBirth',
    type: 'input',
    transform: (value) => {
      // Transform YYYY-MM-DD to MM/DD/YYYY if needed
      if (value.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [year, month, day] = value.split('-');
        return `${month}/${day}/${year}`;
      }
      return value;
    },
    required: true
  },
  'primary_borrower.email': {
    selector: 'input[name="email"], #email',
    type: 'input',
    required: false
  },
  'primary_borrower.phone': {
    selector: 'input[name="phoneNumber"], #phoneNumber',
    type: 'input',
    transform: (value) => value.replace(/\D/g, ''), // Remove non-digits
    required: true
  },
  
  // Address Information
  'primary_borrower.address': {
    selector: 'input[name="street"], #street',
    type: 'input',
    required: true
  },
  'primary_borrower.city': {
    selector: 'input[name="city"], #city',
    type: 'input',
    required: true
  },
  'primary_borrower.state': {
    selector: 'select[name="state"], #state',
    type: 'select',
    required: true
  },
  'primary_borrower.zip': {
    selector: 'input[name="zipCode"], #zipCode',
    type: 'input',
    transform: (value) => value.replace(/\D/g, ''), // Remove non-digits
    required: true
  },
  'primary_borrower.residence_years': {
    selector: 'input[name="yearsAtAddress"], #yearsAtAddress',
    type: 'input',
    required: false
  },
  'primary_borrower.residence_months': {
    selector: 'input[name="monthsAtAddress"], #monthsAtAddress',
    type: 'input',
    required: false
  },
  
  // Employment Information
  'primary_borrower.employer': {
    selector: 'input[name="employerName"], #employerName',
    type: 'input',
    required: true
  },
  'primary_borrower.employment_years': {
    selector: 'input[name="yearsEmployed"], #yearsEmployed',
    type: 'input',
    required: false
  },
  'primary_borrower.employment_months': {
    selector: 'input[name="monthsEmployed"], #monthsEmployed',
    type: 'input',
    required: false
  },
  'primary_borrower.position': {
    selector: 'input[name="position"], #position',
    type: 'input',
    required: false
  },
  'primary_borrower.income': {
    selector: 'input[name="monthlyIncome"], #monthlyIncome',
    type: 'input',
    transform: (value) => {
      // Remove currency symbols and commas, return just the number
      return value.replace(/[$,]/g, '');
    },
    required: true
  },
  
  // Vehicle Information
  'vehicle_data.year': {
    selector: 'input[name="vehicleYear"], #vehicleYear',
    type: 'input',
    required: true
  },
  'vehicle_data.make': {
    selector: 'input[name="vehicleMake"], #vehicleMake',
    type: 'input',
    required: true
  },
  'vehicle_data.model': {
    selector: 'input[name="vehicleModel"], #vehicleModel',
    type: 'input',
    required: true
  },
  'vehicle_data.trim': {
    selector: 'input[name="vehicleTrim"], #vehicleTrim',
    type: 'input',
    required: false
  },
  'vehicle_data.mileage': {
    selector: 'input[name="vehicleMileage"], #vehicleMileage',
    type: 'input',
    transform: (value) => value.replace(/\D/g, ''), // Remove non-digits
    required: false
  },
  'vehicle_data.vin': {
    selector: 'input[name="vin"], #vin',
    type: 'input',
    required: true
  },
  
  // Financial Information
  'financial_data.selling_price': {
    selector: 'input[name="sellingPrice"], #sellingPrice',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: true
  },
  'financial_data.down_payment': {
    selector: 'input[name="cashDown"], #cashDown',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.trade_value': {
    selector: 'input[name="tradeInValue"], #tradeInValue',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.trade_payoff': {
    selector: 'input[name="tradeInPayoff"], #tradeInPayoff',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.tax': {
    selector: 'input[name="taxAmount"], #taxAmount',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.warranty': {
    selector: 'input[name="warrantyAmount"], #warrantyAmount',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.gap': {
    selector: 'input[name="gapAmount"], #gapAmount',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.doc_fees': {
    selector: 'input[name="documentationFee"], #documentationFee',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.title_fees': {
    selector: 'input[name="titleFee"], #titleFee',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.registration_fees': {
    selector: 'input[name="registrationFee"], #registrationFee',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: false
  },
  'financial_data.amount_financed': {
    selector: 'input[name="amountToFinance"], #amountToFinance',
    type: 'input',
    transform: (value) => value.replace(/[$,]/g, ''), // Remove currency symbols and commas
    required: true
  },
  
  // Navigation elements
  'submitButton': {
    selector: 'button[type="submit"], input[type="submit"], .submit-button',
    type: 'button'
  },
  'continueButton': {
    selector: '.continue-button, button:contains("Continue"), button:contains("Next")',
    type: 'button'
  },
  'referenceNumber': {
    selector: '#referenceNumber, .reference-number, .application-id',
    type: 'text'
  },
  'statusIndicator': {
    selector: '.status-indicator, .application-status',
    type: 'text'
  }
};
