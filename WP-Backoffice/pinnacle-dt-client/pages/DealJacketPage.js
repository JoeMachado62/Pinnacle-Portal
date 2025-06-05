// pages/DealJacketPage.js
const BasePage = require('./BasePage');

class DealJacketPage extends BasePage {
    constructor(page, dtConfig) {
        super(page, dtConfig);
        this.selectors = this.dtConfig.selectors.dealJacket; // And other relevant sections
    }

    async isOnPage(dealId = null) {
        try {
            await this.page.waitForSelector(this.selectors.header, { timeout: this.dtConfig.timeouts.shortWait });
            if (dealId) {
                const url = this.page.url();
                return url.includes(dealId); // Or more specific check
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    async getDealStatusText() {
        // This requires knowing where the status is displayed on the Deal Jacket page
        // Example:
        // const statusElement = await this.page.$('#deal_status_display_field');
        // if (statusElement) return statusElement.evaluate(el => el.textContent.trim());
        this.logger.warn('getDealStatusText from DealJacketPage is a placeholder. Implement specific selector.');
        return 'StatusUnknownFromDealJacket';
    }

    async getFullDealJacketData() {
        // Scrape all relevant data from the deal jacket
        this.logger.info('Scraping full deal jacket data (placeholder implementation)...');
        // Example:
        // const sellingPrice = await this.page.$eval('#selling_price_field', el => el.textContent.trim());
        // const customerName = await this.page.$eval('#customer_name_field', el => el.textContent.trim());
        return {
            scrapedStatus: await this.getDealStatusText(),
            // sellingPrice,
            // customerName,
            // ... other fields
            lastScraped: new Date().toISOString()
        };
    }
    
    async getReferenceNumber() {
        this.logger.info(`Attempting to extract DealerTrack Reference Number from Deal Jacket... Current URL: ${this.page.url()}`);
        let dtReferenceNumber = null;
        try {
            await this.page.waitForSelector(this.selectors.header, { timeout: this.dtConfig.timeouts.mediumWait });
             if (await this.page.$(this.selectors.referenceNumberSpan)) {
                dtReferenceNumber = await this.page.$eval(this.selectors.referenceNumberSpan, el => el.textContent.trim());
                dtReferenceNumber = dtReferenceNumber.replace(/[^0-9]/g, ''); // Clean non-numeric
            }
            if (dtReferenceNumber) {
                this.logger.info(`Extracted DT Reference Number from Deal Jacket selector: ${dtReferenceNumber}`);
            }
        } catch (e) {
            this.logger.warn(`Primary selector for DT Reference Number on Deal Jacket not found: ${e.message}`);
        }
        // Fallback from URL if still not found
        if (!dtReferenceNumber) {
            const url = this.page.url();
            let match = url.match(/\/deals\/(\d+)/) || url.match(/\/dealjackets\/(\d+)/);
            if (match && match[1]) {
                dtReferenceNumber = match[1];
                this.logger.info(`Extracted DT Reference Number from Deal Jacket URL: ${dtReferenceNumber}`);
            }
        }
        return dtReferenceNumber;
    }
}
module.exports = DealJacketPage;