// pages/DealListPage.js
const BasePage = require('./BasePage');

class DealListPage extends BasePage {
    constructor(page, dtConfig) {
        super(page, dtConfig);
        this.selectors = this.dtConfig.selectors.dealList;
    }

    async navigateTo() {
        // Might need to click "Active Deals" first from F&I Home, then land on a list
        // For now, assuming direct navigation or already on a page with the search box
        if (!this.page.url().includes(this.dtConfig.paths.dealList)) {
             this.logger.info('Navigating to a generic deal list/search area.');
             // This path might need to be the F&I home, then click 'Active Deals'
             await this.goto(this.dtConfig.paths.fniHome); // Go to F&I Home
             await this.clickElement(this.dtConfig.selectors.activeDealsLink, 'Active Deals Link', {waitUntil: 'networkidle0'});
        }
    }

    async searchByCustomerNameOrDealID(searchTerm) {
        this.logger.info(`Searching deal list for: ${searchTerm}`);
        await this.typeIntoField(this.selectors.searchInput, searchTerm, 'Deal List Search Input');
        // DealerTrack might auto-submit on type, or require Enter, or have a search button
        // The user's example implies waiting for a response after pressing Enter
        await Promise.all([
            this.page.waitForResponse(r => r.url().includes('/dealjackets/') && r.status() === 200, {timeout: this.dtConfig.timeouts.longWait}), // Adjust URL part
            this.page.press(this.selectors.searchInput, 'Enter')
        ]);
        this.logger.info('Deal list search submitted.');
         await this.page.waitForTimeout(this.dtConfig.timeouts.shortWait); // Allow table to render
    }

    async findAndOpenDeal(criteria = { customerName: null, dealId: null, appDate: null }) {
        // This is a more complex operation: iterate rows, match criteria, click.
        // For simplicity, if searching by dealId (dtReferenceNumber) often lands you directly or with one result.
        // The user's example `pickDealByDate` is a good approach for date matching.
        this.logger.info(`Attempting to find and open deal with criteria: ${JSON.stringify(criteria)}`);
        const rows = await this.page.$$(this.selectors.resultsTableRows);
        if (rows.length === 0) {
            throw new Error('No deals found in the list after search.');
        }

        // Example: Find by exact Deal ID if provided (assuming dealId is visible or part of a data attribute)
        if (criteria.dealId) {
            for (const row of rows) {
                const rowText = await row.evaluate(node => node.innerText);
                if (rowText.includes(criteria.dealId)) {
                    this.logger.info(`Found matching deal row for ID ${criteria.dealId}. Clicking...`);
                    await this.clickElement(row, `Deal Row for ID ${criteria.dealId}`, { waitUntil: 'networkidle0' });
                    return true; // Found and clicked
                }
            }
        }
        // Add more sophisticated matching logic here (customer name, date proximity)

        throw new Error(`Could not find a suitable deal matching criteria: ${JSON.stringify(criteria)}`);
    }
}
module.exports = DealListPage;