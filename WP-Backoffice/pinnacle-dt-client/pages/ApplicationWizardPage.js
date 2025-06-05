// pages/ApplicationWizardPage.js
const BasePage = require('./BasePage');

class ApplicationWizardPage extends BasePage {
    constructor(page, dtConfig, appData) {
        super(page, dtConfig);
        this.appData = appData;
        this.s = { // Shorthand for selectors
            prefs: this.dtConfig.selectors.appPrefs,
            app: this.dtConfig.selectors.applicantForm,
            coapp: this.dtConfig.selectors.coApplicantForm,
            vehicle: this.dtConfig.selectors.vehicleForm,
            dealJacket: this.dtConfig.selectors.dealJacket,
        };
        this.m = { // Shorthand for mappings
            housing: this.dtConfig.housingStatusMap,
            employment: this.dtConfig.employmentStatusMap,
            payFrequency: this.dtConfig.payFrequencyMap,
        }
    }

    async selectApplicationPreferences() {
        this.logger.info('Setting up application preferences...');
        await this.clickElement(this.s.prefs.assetTypeAutoRadio, 'Asset Type Auto');
        await this.clickElement(this.s.prefs.vehicleConditionUsedRadio, 'Vehicle Condition Used'); // Defaulting to Used

        if (this.appData.co_borrower && Object.keys(this.appData.co_borrower).length > 0) {
            await this.clickElement(this.s.prefs.hasCoapplicantYesRadio, 'Has Co-applicant Yes');
        } else {
            await this.clickElement(this.s.prefs.hasCoapplicantNoRadio, 'Has Co-applicant No');
        }
        await this.clickElement(this.s.prefs.productTypeRetailRadio, 'Product Type Retail');
        
        await this.clickElement(this.s.prefs.nextButton, 'Next Button on App Prefs', { waitUntil: 'networkidle0' });
        this.logger.info(`Navigated to main applicant info form: ${this.page.url()}`);
    }

    async _fillPersonalInformation(borrowerData, formSelectors, prefix = 'App') {
        const personal = borrowerData.personalInformation;
        const address = borrowerData.addressInformation.current;
        // Name may be pre-filled from search, or entered here.
        // Assuming name fields need to be filled if not pre-filled.
        const nameParts = personal.applicantName.split(' ');
        const firstName = nameParts[0];
        const lastName = nameParts.slice(1).join(' ');

        await this.typeIntoField(formSelectors.firstName, firstName, `${prefix} First Name`);
        await this.typeIntoField(formSelectors.lastName, lastName, `${prefix} Last Name`);
        // await this.typeIntoField(formSelectors.middleName, personal.middleInitial, `${prefix} Middle Initial`);
        // await this.selectDropdownByValue(formSelectors.suffixCode, personal.suffix, `${prefix} Suffix`);
        await this.typeIntoField(formSelectors.taxId, personal.ssn, `${prefix} SSN`); // Ensure ssn is decrypted
        await this.typeIntoField(formSelectors.birthDate, personal.dateOfBirth, `${prefix} DOB`);

        await this.typeIntoField(formSelectors.addressLine1, address.address, `${prefix} Address 1`);
        // await this.typeIntoField(formSelectors.addressLine2, address.address2, `${prefix} Address 2`);
        await this.typeIntoField(formSelectors.zipCode, address.zip, `${prefix} Zip`);
        // Wait for city/state to potentially auto-populate or enable, then fill
        await this.page.waitForTimeout(this.dtConfig.timeouts.shortWait); // For potential auto-population
        if (await this.page.$(formSelectors.cityStateDropdown)) { // If a combined dropdown
             // await this.selectDropdownByValue(formSelectors.cityStateDropdown, address.cityStateValue, `${prefix} City/State`);
        } else { // If separate fields
            await this.typeIntoField(formSelectors.city, address.city, `${prefix} City`);
            await this.selectDropdownByValue(formSelectors.state, address.state, `${prefix} State`);
        }

        await this.typeIntoField(formSelectors.primaryPhoneNumber, personal.homePhone, `${prefix} Home Phone`);
        await this.typeIntoField(formSelectors.alternatePhoneNumber, personal.cellPhone, `${prefix} Cell Phone`);
        if (personal.email) {
            await this.typeIntoField(formSelectors.emailAddress, personal.email, `${prefix} Email`);
        } else if (await this.page.$(formSelectors.emailNotProvidedCheckbox)) {
            await this.clickElement(formSelectors.emailNotProvidedCheckbox, `${prefix} Email Not Provided`);
        }
        
        // Assuming driversLicenseState is part of personal.driversLicense (e.g. "CA:12345") or separate field in appData
        const dlParts = (personal.driversLicense || "").split(':'); // Example if DL includes state
        const dlState = dlParts.length > 1 ? dlParts[0] : personal.driversLicenseState; // Get from appData
        const dlNumber = dlParts.length > 1 ? dlParts[1] : personal.driversLicense;   // Get from appData, ensure decrypted

        await this.selectDropdownByValue(formSelectors.driversLicenseStateCode, dlState, `${prefix} DL State`);
        await this.typeIntoField(formSelectors.driversLicenseNumber, dlNumber, `${prefix} DL Number`);
        // await this.selectDropdownByValue(formSelectors.maritalStatusCode, personal.maritalStatusMapped, `${prefix} Marital Status`);
    }

    async _fillHousingAndEmployment(borrowerData, formSelectors, prefix = 'App') {
        const housing = borrowerData.addressInformation.current;
        const employment = borrowerData.employmentInformation.current;

        await this.selectDropdownByValue(formSelectors.housingStatusCode, this.m.housing[housing.addressType] || housing.addressType, `${prefix} Housing Status`);
        await this.typeIntoField(formSelectors.currentAddressYears, String(housing.years || 0), `${prefix} Yrs at Addr`);
        await this.typeIntoField(formSelectors.currentAddressMonths, String(housing.months || 0), `${prefix} Mos at Addr`);
        if (housing.addressType !== 'own_outright') { // Example condition
             await this.typeIntoField(formSelectors.monthlyHousingPayment, String(housing.monthlyPayment || ''), `${prefix} Rent/Mortgage`);
        }
        // TODO: Implement previous address logic based on years/months at current address

        await this.selectDropdownByValue(formSelectors.employmentStatusCode, this.m.employment[employment.employmentStatus] || employment.employmentStatus, `${prefix} Emp Status`);
        await this.typeIntoField(formSelectors.employerName, employment.employerName, `${prefix} Employer`);
        await this.typeIntoField(formSelectors.employedYears, String(employment.years || 0), `${prefix} Yrs at Emp`);
        await this.typeIntoField(formSelectors.employedMonths, String(employment.months || 0), `${prefix} Mos at Emp`);
        await this.typeIntoField(formSelectors.workPhoneNumber, employment.phone, `${prefix} Work Phone`);
        await this.typeIntoField(formSelectors.occupationName, employment.title, `${prefix} Occupation`);
        await this.typeIntoField(formSelectors.salary, String(employment.income || ''), `${prefix} Salary`);
        await this.selectDropdownByValue(formSelectors.salaryTypeCode, this.m.payFrequency[employment.payFrequency] || employment.payFrequency, `${prefix} Salary Freq`);
        // TODO: Implement previous employment logic
    }

    async fillApplicantInformation() {
        this.logger.info('Filling Applicant Information...');
        const applicant = this.appData.primary_borrower;
        await this._fillPersonalInformation(applicant, this.s.app, 'App');
        await this._fillHousingAndEmployment(applicant, this.s.app, 'App');
    }

    async fillCoApplicantInformation() {
        if (!this.appData.co_borrower || Object.keys(this.appData.co_borrower).length === 0) {
            return;
        }
        this.logger.info('Filling Co-Applicant Information...');
        const coApplicant = this.appData.co_borrower;
        // await this.selectDropdownByValue(this.s.coapp.partyRelationshipCode, coApplicant.relationshipMapped, 'CoApp Relationship');
        await this._fillPersonalInformation(coApplicant, this.s.coapp, 'CoApp');
        // Check for "Same address as applicant" checkbox
        if (coApplicant.addressInformation.current.isSameAsPrimary) { // Assuming this flag exists in your data
            await this.clickElement(this.s.coapp.sameAddressCheckbox, 'CoApp Same Address Checkbox');
        } else {
            // Fill co-app address explicitly if different (covered by _fillPersonalInformation if selectors are right)
        }
        await this._fillHousingAndEmployment(coApplicant, this.s.coapp, 'CoApp');
    }

    async fillVehicleAndFinancingInformation() {
        this.logger.info('Filling Vehicle & Financing Information...');
        const veh = this.appData.vehicle_data;
        const fin = this.appData.financial_data;

        await this.selectDropdownByValue(this.s.vehicle.yearDropdown, veh.year, 'Veh Year');
        await this.page.waitForTimeout(this.dtConfig.timeouts.shortWait); // For dependent dropdowns to load
        await this.selectDropdownByValue(this.s.vehicle.makeDropdown, veh.makeId || veh.make, 'Veh Make'); // Use makeId if available
        await this.page.waitForTimeout(this.dtConfig.timeouts.shortWait);
        await this.selectDropdownByValue(this.s.vehicle.modelDropdown, veh.modelId || veh.makeAndModel.replace(veh.make, '').trim(), 'Veh Model');
        await this.page.waitForTimeout(this.dtConfig.timeouts.shortWait);
        // await this.selectDropdownByValue(this.s.vehicle.trimDropdown, veh.trimId || veh.trim, 'Veh Trim');

        // Example: if veh.paymentCall is true/false
        // if (veh.paymentCall) {
        //    await this.clickElement(this.s.vehicle.paymentCallYesRadio, 'Payment Call Yes');
        // } else {
           await this.clickElement(this.s.vehicle.paymentCallNoRadio, 'Payment Call No');
        // }

        await this.typeIntoField(this.s.vehicle.stockNumberInput, veh.stockNumber, 'Veh Stock Number');
        await this.typeIntoField(this.s.vehicle.vinInput, veh.vin, 'Veh VIN');
        await this.typeIntoField(this.s.vehicle.odometerInput, veh.mileage, 'Veh Odometer');

        await this.typeIntoField(this.s.vehicle.sellingPriceInput, String(fin.sellingPrice), 'Selling Price');
        await this.typeIntoField(this.s.vehicle.termInput, String(fin.termMonths || this.appData.financial_data.termMonths), 'Term Months');
        await this.typeIntoField(this.s.vehicle.salesTaxInput, String(fin.taxes), 'Sales Tax');
        await this.typeIntoField(this.s.vehicle.titleLicenseFeeInput, String(fin.titleFees), 'Title & License Fees');
        await this.typeIntoField(this.s.vehicle.cashDownInput, String(fin.totalCashDown), 'Cash Down');
        await this.typeIntoField(this.s.vehicle.docFeeInput, String(fin.docFees), 'Doc Fees');
        // await this.typeIntoField(this.s.vehicle.rebateInput, String(fin.rebates), 'Rebates');
        await this.typeIntoField(this.s.vehicle.netTradeInput, String(fin.netTradeValue), 'Net Trade Value');

        // ... fill other financial fields as needed
    }

    async handleDisclosuresAndSubmit() {
        this.logger.info('Handling Disclosures & Submitting Application...');
        if (this.appData.co_borrower && Object.keys(this.appData.co_borrower).length > 0) {
            await this.clickElement(this.s.vehicle.disclosureJointRadio, 'Reg B Joint Credit Disclosure');
        } else {
            await this.clickElement(this.s.vehicle.disclosureIndividualRadio, 'Individual Credit Disclosure');
        }
        // await this.typeIntoField(this.s.vehicle.commentsInput, this.appData.comments, 'Comments');

        await this.clickElement(this.s.vehicle.submitApplicationButton, 'Final Submit Application Button', { waitUntil: 'networkidle0' });
        this.logger.info('Application submitted. Waiting for next page (Deal Jacket or confirmation)...');
    }

    async extractDealReferenceNumber() {
        this.logger.info(`Attempting to extract DealerTrack Reference Number... Current URL: ${this.page.url()}`);
        let dtReferenceNumber = null;
        try {
            // Wait for a known element on the Deal Jacket summary page
            await this.page.waitForSelector(this.s.dealJacket.header, { timeout: this.dtConfig.timeouts.navigation });
            
            // Try the specific selector from config
            if (await this.page.$(this.s.dealJacket.referenceNumberSpan)) {
                dtReferenceNumber = await this.page.$eval(this.s.dealJacket.referenceNumberSpan, el => el.textContent.trim());
                // Clean up "Deal # " or similar prefixes if they exist
                dtReferenceNumber = dtReferenceNumber.replace(/[^0-9]/g, ''); 
            }

            if (dtReferenceNumber) {
                this.logger.info(`Extracted DT Reference Number using selector: ${dtReferenceNumber}`);
            }

        } catch (e) {
            this.logger.warn(`Primary selector for DT Reference Number not found or timed out: ${e.message}`);
        }
        
        // Fallback: Try to get the deal ID from the URL
        if (!dtReferenceNumber) {
            const url = this.page.url();
            // Example URL patterns: /dealjackets/DEAL_JACKET_ID/deals/DEAL_ID/
            // or /deals/DEAL_ID/summary
            let match = url.match(/\/deals\/(\d+)/) || url.match(/\/dealjackets\/(\d+)/);
            if (match && match[1]) {
                dtReferenceNumber = match[1];
                this.logger.info(`Extracted DT Reference Number from URL: ${dtReferenceNumber}`);
            }
        }

        if (!dtReferenceNumber) {
            this.logger.error('Failed to extract DealerTrack Reference Number from page content or URL.');
            throw new Error('Failed to extract DealerTrack Reference Number after submission.');
        }
        return dtReferenceNumber;
    }
}
module.exports = ApplicationWizardPage;