// pages/CustomerSearchPage.js
const BasePage = require('./BasePage');

class CustomerSearchPage extends BasePage {
    constructor(page, dtConfig) {
        super(page, dtConfig);
        this.selectors = this.dtConfig.selectors.customerSearch;
    }

    async navigateTo() {
        await this.goto(this.dtConfig.paths.newAppStart);
    }

    async startNewApplicationViaQuickLink() {
        this.logger.info('Clicking "Credit App" quick link to start new application...');
        // This assumes the quick link is available on the current page (e.g., F&I Home)
        // If not, navigate to F&I home first.
        const fniHomeSubstring = this.dtConfig.paths.fniHome;
        if (!this.page.url().includes(fniHomeSubstring)) {
            this.logger.info(`Not on F&I home. Navigating to ${fniHomeSubstring}`);
            await this.goto(fniHomeSubstring);
        }
        await this.clickElement(this.dtConfig.selectors.creditAppQuickLink, 'Credit App Quick Link', { waitUntil: 'networkidle0' });
        this.logger.info(`Navigated to customer search / new app start page: ${this.page.url()}`);
    }
    
    async enterNameAndSearch({ firstName, lastName, ssn, businessName }) {
        if (businessName) {
            await this.clickElement(this.selectors.businessRadio, 'Business Radio');
            await this.typeIntoField(this.selectors.businessNameInput, businessName, 'Business Name');
        } else {
            await this.clickElement(this.selectors.individualRadio, 'Individual Radio');
            await this.typeIntoField(this.selectors.firstNameInput, firstName, 'First Name');
            await this.typeIntoField(this.selectors.lastNameInput, lastName, 'Last Name');
            if (ssn) { // SSN might be optional at this stage or part of app form
                await this.typeIntoField(this.selectors.ssnInput, ssn, 'SSN');
            }
        }
        await this.clickElement(this.selectors.searchButton, 'Customer Search Button', { waitUntil: 'networkidle0' });
    }

    async hasDuplicates() {
        // Check if the "Possible Duplicates" table/section appears
        try {
            await this.page.waitForSelector(this.selectors.possibleDuplicatesTable, { timeout: this.dtConfig.timeouts.shortWait });
            const rows = await this.page.$$(this.selectors.possibleDuplicatesRows);
            return rows.length > 0;
        } catch (e) {
            return false; // No duplicates table found
        }
    }

    async proceedWithNewApplication() {
        // If duplicates are shown, there might be a "Continue with New Application" button
        if (await this.hasDuplicates()) {
             this.logger.info('Duplicates found, attempting to click "Continue with New Application".');
             await this.clickElement(this.selectors.continueWithNewButton, 'Continue With New Button', { waitUntil: 'networkidle0' });
        }
        // If no duplicates, the page might have already proceeded or requires a generic "Next"
        // This method assumes that after search (and optionally handling duplicates),
        // we are ready to move to the app prefs/wizard.
    }
}
module.exports = CustomerSearchPage;