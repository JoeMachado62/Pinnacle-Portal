// src/mappings/fieldMappings.js
module.exports = {
    // For all primary applicant fields
    applicant: {
        // Personal Information section
        personalInformation: {
            // Map each field with selector, transformation, and validation
            firstName: {
                selector: '#id_applicant_form-first_name',
                transform: value => value.trim(),
                required: true,
                validation: value => value.length > 0,
                errorMsg: 'First name is required'
            },
            lastName: {
                selector: '#id_applicant_form-last_name',
                transform: value => value.trim(),
                required: true,
                validation: value => value.length > 0,
                errorMsg: 'Last name is required'
            },
            middleName: {
                selector: '#id_applicant_form-middle_name',
                transform: value => value ? value.trim() : '',
                required: false
            },
            ssn: {
                selector: '#id_applicant_form-tax_id',
                // Format as XXX-XX-XXXX for DealerTrack
                transform: value => {
                    // Remove any non-digits
                    const digits = value.replace(/\D/g, '');
                    if (digits.length !== 9) return digits;
                    // Format as XXX-XX-XXXX
                    return `${digits.slice(0, 3)}-${digits.slice(3, 5)}-${digits.slice(5)}`;
                },
                required: true,
                validation: value => /^\d{9}$|^\d{3}-\d{2}-\d{4}$/.test(value),
                errorMsg: 'Valid SSN is required (9 digits)'
            },
            dateOfBirth: {
                selector: '#id_applicant_form-birth_date',
                // Format date as MM/DD/YYYY for DealerTrack
                transform: value => {
                    // Handle various possible input formats
                    if (!value) return '';
                    try {
                        const date = new Date(value);
                        return `${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}/${date.getFullYear()}`;
                    } catch (e) {
                        return value; // Return as is if parsing fails
                    }
                },
                required: true,
                validation: value => /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(value),
                errorMsg: 'Valid birth date required (MM/DD/YYYY)'
            },
            driversLicense: {
                selector: '#id_applicant_form-drivers_license',
                transform: value => value ? value.trim() : '',
                required: false
            },
            driversLicenseState: {
                selector: '#id_applicant_form-drivers_license_us_state_code',
                transform: value => value ? value.toUpperCase() : '',
                required: false
            },
            email: {
                selector: '#id_applicant_form-email_address',
                transform: value => value ? value.trim().toLowerCase() : '',
                required: false,
                // If email is empty, need to check the "no email" checkbox
                emptyAction: async (page) => {
                    await page.click('#id_applicant_form-email_not_provided');
                }
            },
            // Add all other personal information fields
        },
        
        // Address Information section with conditional logic for previous addresses
        addressInformation: {
            current: {
                address: {
                    selector: '#id_applicant_form-line_1_address',
                    transform: value => value.trim(),
                    required: true
                },
                address2: {
                    selector: '#id_applicant_form-line_2_address',
                    transform: value => value ? value.trim() : '',
                    required: false
                },
                city: {
                    selector: '#id_applicant_form-city',
                    transform: value => value.trim(),
                    required: true,
                    // Only appears if city/state selection is "Other"
                    conditionalSelector: true,
                    condition: async (page) => {
                        // Check if dropdown exists and select "Other" if so
                        const dropdownExists = await page.evaluate(() => !!document.querySelector('#id_applicant_form-city_state_dropdown'));
                        if (dropdownExists) {
                            await page.select('#id_applicant_form-city_state_dropdown', 'Other');
                            // Wait for city field to appear
                            await page.waitForSelector('#id_applicant_form-city', { visible: true, timeout: 5000 });
                        }
                        return true;
                    }
                },
                state: {
                    selector: '#id_applicant_form-us_state_code',
                    transform: value => value.toUpperCase(),
                    required: true,
                    // Only appears if city/state selection is "Other"
                    conditionalSelector: true,
                    condition: async (page) => {
                        // Check if dropdown exists and select "Other" if so
                        const dropdownExists = await page.evaluate(() => !!document.querySelector('#id_applicant_form-city_state_dropdown'));
                        if (dropdownExists) {
                            await page.select('#id_applicant_form-city_state_dropdown', 'Other');
                            // Wait for state field to appear
                            await page.waitForSelector('#id_applicant_form-us_state_code', { visible: true, timeout: 5000 });
                        }
                        return true;
                    }
                },
                zip: {
                    selector: '#id_applicant_form-zip_code',
                    transform: value => value.trim(),
                    required: true
                },
                addressType: {
                    selector: '#id_applicant_form-housing_status_code',
                    // Map WordPress address types to DealerTrack housing status codes
                    transform: value => {
                        const housingStatusMap = {
                            'own': 'O',       // Own
                            'mortgage': 'M',  // Mortgage
                            'rent': 'R',      // Rent
                            'family': 'F',    // Family
                            'military': 'X',  // Military Housing
                            'other': 'E'      // Other
                        };
                        return housingStatusMap[value.toLowerCase()] || 'E';
                    },
                    required: true
                },
                years: {
                    selector: '#id_applicant_form-current_address_years',
                    transform: value => value.toString(),
                    required: true
                },
                months: {
                    selector: '#id_applicant_form-current_address_months',
                    transform: value => value.toString(),
                    required: true
                },
                monthlyPayment: {
                    selector: '#id_applicant_form-mortgage_payment_or_rent',
                    transform: value => value ? value.toString() : '',
                    required: false,
                    // Only appears if housing status is not "Own Outright"
                    conditionalSelector: true,
                    condition: async (page) => {
                        const housingStatus = await page.$eval('#id_applicant_form-housing_status_code', el => el.value);
                        return housingStatus !== 'N'; // N = Own Outright
                    }
                }
            },
            
            // Previous address mapping - only shown if current address < 2 years
            previous: {
                // Flag to determine if section should be shown
                shouldShow: data => {
                    const years = parseInt(data.addressInformation.current.years || 0);
                    const months = parseInt(data.addressInformation.current.months || 0);
                    return (years * 12 + months) < 24;
                },
                
                address: {
                    selector: '#id_applicant_form-previous_line_1_address',
                    transform: value => value.trim(),
                    required: true,
                    conditionalSelector: true,
                    condition: (data) => {
                        return data.addressInformation.previous.shouldShow(data);
                    }
                },
                address2: {
                    selector: '#id_applicant_form-previous_line_2_address',
                    transform: value => value ? value.trim() : '',
                    required: false
                },
                zip: {
                    selector: '#id_applicant_form-previous_zip_code',
                    transform: value => value.trim(),
                    required: true
                },
                city: {
                    selector: '#id_applicant_form-previous_city',
                    transform: value => value.trim(),
                    required: true,
                    conditionalSelector: true,
                    condition: async (page) => {
                        // Check if dropdown exists and select "Other" if so
                        const dropdownExists = await page.evaluate(() => !!document.querySelector('#id_applicant_form-previous_city_state_dropdown'));
                        if (dropdownExists) {
                            await page.select('#id_applicant_form-previous_city_state_dropdown', 'Other');
                            // Wait for city field to appear
                            await page.waitForSelector('#id_applicant_form-previous_city', { visible: true, timeout: 5000 });
                        }
                        return true;
                    }
                },
                state: {
                    selector: '#id_applicant_form-previous_us_state_code',
                    transform: value => value.toUpperCase(),
                    required: true,
                    conditionalSelector: true,
                    condition: async (page) => {
                        // Check if dropdown exists and select "Other" if so
                        const dropdownExists = await page.evaluate(() => !!document.querySelector('#id_applicant_form-previous_city_state_dropdown'));
                        if (dropdownExists) {
                            await page.select('#id_applicant_form-previous_city_state_dropdown', 'Other');
                            // Wait for state field to appear
                            await page.waitForSelector('#id_applicant_form-previous_us_state_code', { visible: true, timeout: 5000 });
                        }
                        return true;
                    }
                },
                years: {
                    selector: '#id_applicant_form-previous_address_years',
                    transform: value => value.toString(),
                    required: true
                },
                months: {
                    selector: '#id_applicant_form-previous_address_months',
                    transform: value => value.toString(),
                    required: true
                }
            }
        },
        
        // Employment Information with dynamic fields based on employment status
        employmentInformation: {
            current: {
                status: {
                    selector: '#id_applicant_form-employment_status_code',
                    // Map to DealerTrack employment status codes
                    transform: value => {
                        const statusMap = {
                            'employed': 'W',        // Employed
                            'unemployed': 'U',      // Unemployed
                            'retired': 'T',         // Retired
                            'active_military': 'M', // Active Military
                            'other': 'O',           // Other
                            'self_employed': 'S',   // Self Employed
                            'student': 'N',         // Student
                            'retired_military': 'E' // Retired Military
                        };
                        return statusMap[value.toLowerCase()] || 'O';
                    },
                    required: true,
                    // This field determines the visibility of many other fields
                    onChange: async (page, value) => {
                        // Wait for form to update based on selection
                        await page.waitForTimeout(1000);
                    }
                },
                // Dynamic fields based on employment status
                employerName: {
                    selector: '#id_applicant_form-organization_name',
                    transform: value => value ? value.trim() : '',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status); // Only for employed, military, other, self-employed
                    }
                },
                years: {
                    selector: '#id_applicant_form-current_employed_years',
                    transform: value => value ? value.toString() : '0',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status); // Only for employed, military, other, self-employed
                    }
                },
                months: {
                    selector: '#id_applicant_form-current_employed_months',
                    transform: value => value ? value.toString() : '0',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status); // Only for employed, military, other, self-employed
                    }
                },
                phone: {
                    selector: '#id_applicant_form-work_phone_number',
                    transform: value => {
                        // Format as (XXX) XXX-XXXX
                        if (!value) return '';
                        const digits = value.replace(/\D/g, '');
                        if (digits.length !== 10) return value;
                        return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
                    },
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status); // Only for employed, military, other, self-employed
                    }
                },
                occupation: {
                    selector: '#id_applicant_form-occupation_name',
                    transform: value => value ? value.trim() : '',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status); // Only for employed, military, other, self-employed
                    }
                },
                salary: {
                    selector: '#id_applicant_form-salary',
                    transform: value => value ? value.toString() : '',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S', 'T', 'N', 'E'].includes(status); // All except unemployed
                    }
                },
                salaryFrequency: {
                    selector: '#id_applicant_form-salary_type_code',
                    transform: value => {
                        const frequencyMap = {
                            'weekly': 'W',
                            'biweekly': 'B',
                            'monthly': 'M',
                            'annually': 'A'
                        };
                        return frequencyMap[value.toLowerCase()] || 'M';
                    },
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S', 'T', 'N', 'E'].includes(status); // All except unemployed
                    }
                },
                school: {
                    selector: '#id_applicant_form-school',
                    transform: value => value ? value.trim() : '',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page) => {
                        const status = await page.$eval('#id_applicant_form-employment_status_code', el => el.value);
                        return status === 'N'; // Only for students
                    }
                }
            },
            
            // Previous employment - only shown if current employment < 2 years
            previous: {
                // Flag to determine if section should be shown
                shouldShow: data => {
                    // If status is retired or retired military, don't show previous employment
                    if (['T', 'E'].includes(data.employmentInformation.current.status)) {
                        return false;
                    }
                    
                    const years = parseInt(data.employmentInformation.current.years || 0);
                    const months = parseInt(data.employmentInformation.current.months || 0);
                    return (years * 12 + months) < 24;
                },
                
                status: {
                    selector: '#id_applicant_form-previous_employment_status_code',
                    transform: value => {
                        const statusMap = {
                            'employed': 'W',
                            'unemployed': 'U',
                            'retired': 'T',
                            'active_military': 'M',
                            'other': 'O',
                            'self_employed': 'S',
                            'student': 'N',
                            'retired_military': 'E'
                        };
                        return statusMap[value.toLowerCase()] || 'O';
                    },
                    required: true,
                    conditionalSelector: true,
                    condition: (data) => data.employmentInformation.previous.shouldShow(data)
                },
                
                // Additional previous employment fields (with conditional logic similar to current employment)
                employerName: {
                    selector: '#id_applicant_form-previous_organization_name',
                    transform: value => value ? value.trim() : '',
                    required: false,
                    conditionalSelector: true,
                    condition: async (page, data) => {
                        if (!data.employmentInformation.previous.shouldShow(data)) return false;
                        const status = await page.$eval('#id_applicant_form-previous_employment_status_code', el => el.value);
                        return ['W', 'M', 'O', 'S'].includes(status);
                    }
                },
                // Add all other previous employment fields with similar conditional logic
            }
        },
        
        // Other Income Section
        otherIncome: {
            amount: {
                selector: '#id_applicant_form-other_monthly_income',
                transform: value => value ? value.toString() : '',
                required: false
            },
            source: {
                selector: '#id_applicant_form-other_income_source',
                transform: value => value ? value.trim() : '',
                required: false
            }
        }
    },
    
    // Co-applicant mapping with similar structure to applicant
    coApplicant: {
        // First check if co-applicant exists
        exists: data => !!data.co_borrower,
        
        // Initial checkbox for "Same as Applicant Address"
        sameAddressCheckbox: {
            selector: '#id_co_applicant_form-same_address',
            shouldCheck: data => data.co_borrower?.sameAddressAsApplicant === true
        },
        
        relationship: {
            selector: '#id_co_applicant_form-party_relationship_code',
            transform: value => {
                const relationshipMap = {
                    'spouse': 'S',
                    'relative': 'R',
                    'other': 'O'
                };
                return relationshipMap[value.toLowerCase()] || 'O';
            },
            required: true
        },
        
        // Personal Information similar to applicant
        personalInformation: {
            firstName: {
                selector: '#id_co_applicant_form-first_name',
                transform: value => value.trim(),
                required: true
            },
            // Add all co-applicant personal information fields
        },
        
        // Address Information similar to applicant
        addressInformation: {
            // Conditional on whether "Same as Applicant" is checked
            shouldFillAddress: data => !(data.co_borrower?.sameAddressAsApplicant === true),
            
            current: {
                // Add all address fields with conditional logic
            },
            
            previous: {
                // Previous address logic
            }
        },
        
        // Employment Information similar to applicant
        employmentInformation: {
            // Employment fields with conditional logic
        }
    },
    
    // Vehicle Information Section
    vehicle: {
        type: {
            selector: '#id_vehicle_form-asset_type_1',
            action: 'click', // This is a radio button
            value: true,  // Auto is selected by default
            required: true
        },
        condition: {
            selector: '#id_vehicle_form-condition_type_2',  // Used
            action: 'click', // This is a radio button
            value: data => data.vehicle_data.condition === 'used', // Used is value 2
            required: true
        },
        productType: {
            selector: '#id_vehicle_form-product_type_1',  // Retail
            action: 'click', // This is a radio button
            value: true, // Retail is selected by default
            required: true
        },
        year: {
            selector: '#id_vehicle_form-year_id',
            transform: value => value.trim(),
            required: true
        },
        make: {
            selector: '#id_vehicle_form-make_id',
            transform: value => value,
            required: true,
            waitAfter: true, // Need to wait for model options to load
        },
        model: {
            selector: '#id_vehicle_form-model_id',
            transform: value => value,
            required: true,
            waitAfter: true, // Need to wait for trim options to load
        },
        trim: {
            selector: '#id_vehicle_form-trim_id',
            transform: value => value,
            required: false
        },
        vin: {
            selector: '#id_vehicle_form-vin_number',
            transform: value => value ? value.trim().toUpperCase() : '',
            required: false,
            waitAfter: true, // VIN decoder might populate fields
        },
        mileage: {
            selector: '#id_vehicle_form-odometer_number',
            transform: value => value ? value.toString() : '',
            required: false
        },
        stockNumber: {
            selector: '#id_vehicle_form-stock_number',
            transform: value => value ? value.trim() : '',
            required: false
        }
    },
    
    // Financial/Terms Section
    financial: {
        termMonths: {
            selector: '#id_vehicle_form-term_count',
            transform: value => value ? value.toString() : '',
            required: true
        },
        sellingPrice: {
            selector: '#id_vehicle_form-cash_sell_price_amount',
            transform: value => value ? value.toString() : '0',
            required: true
        },
        salesTax: {
            selector: '#id_vehicle_form-sales_tax_amount',
            transform: value => value ? value.toString() : '0',
            required: true
        },
        titleFees: {
            selector: '#id_vehicle_form-title_and_license_amount',
            transform: value => value ? value.toString() : '0',
            required: true
        },
        cashDown: {
            selector: '#id_vehicle_form-cash_down_amount',
            transform: value => value ? value.toString() : '0',
            required: true
        },
        docFees: {
            selector: '#id_vehicle_form-front_end_fee_amount',
            transform: value => value ? value.toString() : '0',
            required: true
        }
    },
    
    // Disclosure Section (last page)
    disclosure: {
        jointCredit: {
            selector: '#id_vehicle_form-disclosures_1',
            action: 'click',
            value: data => !!data.co_borrower,
            required: true
        },
        individualCredit: {
            selector: '#id_vehicle_form-disclosures_2',
            action: 'click',
            value: data => !data.co_borrower,
            required: true
        }
    },
    
    // Submit buttons
    buttons: {
        next: {
            selector: '#btn_app_pref_submit',
            action: 'click'
        },
        submit: {
            selector: 'button[type="submit"].btn-primary',
            action: 'click'
        }
    }
};