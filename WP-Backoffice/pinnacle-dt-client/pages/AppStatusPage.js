// pages/AppStatusPage.js
const BasePage = require('./BasePage');

class AppStatusPage extends BasePage {
    constructor(page, dtConfig) {
        super(page, dtConfig);
        this.selectors = this.dtConfig.selectors.appStatusSearch;
    }

    async navigateTo() {
        await this.goto(this.dtConfig.paths.appStatusSearch);
    }

    async searchForApplication(dtReferenceNumber) {
        this.logger.info(`Searching for application status with DT Ref: ${dtReferenceNumber}`);
        await this.typeIntoField(this.selectors.searchInput, dtReferenceNumber, 'App Status Search Input');
        await this.clickElement(this.selectors.searchButton, 'App Status Search Button', { waitUntil: 'networkidle0' });
        this.logger.info('App status search submitted.');
    }

    async getFirstResultStatusText() {
        try {
            await this.page.waitForSelector(this.selectors.firstResultStatusColumn, { timeout: this.dtConfig.timeouts.mediumWait });
            const statusText = await this.page.$eval(this.selectors.firstResultStatusColumn, el => el.textContent.trim());
            this.logger.info(`Raw status from DealerTrack table: ${statusText}`);
            return statusText;
        } catch (error) {
            this.logger.warn(`Could not find status in results table for the first result. Application might not be found or page structure changed. ${error.message}`);
            throw new Error(`Failed to find application status in results table. Error: ${error.message}`);
        }
    }

    // Optional: Click to go to Deal Jacket from search results
    async navigateToDealJacketFromFirstResult() {
        try {
            await this.page.waitForSelector(this.selectors.firstResultDealLink, { timeout: this.dtConfig.timeouts.mediumWait });
            await this.clickElement(this.selectors.firstResultDealLink, 'First Result Deal Link', { waitUntil: 'networkidle0' });
            this.logger.info('Navigated to Deal Jacket from app status results.');
            // Now you'd typically use a DealJacketPage object
            return true;
        } catch (error) {
            this.logger.error(`Could not navigate to Deal Jacket from search results: ${error.message}`);
            return false;
        }
    }
}
module.exports = AppStatusPage;