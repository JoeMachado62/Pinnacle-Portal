// pages/BasePage.js
const logger = require('../src/logger'); // Adjust path if BasePage is outside src

class BasePage {
    constructor(page, dtConfig) {
        this.page = page;
        this.dtConfig = dtConfig;
        this.logger = logger; // Make logger available
    }

    async goto(path, options = { waitUntil: 'networkidle0', timeout: this.dtConfig.timeouts.navigation }) {
        const url = this.dtConfig.baseUrl + path;
        this.logger.info(`Navigating to: ${url}`);
        await this.page.goto(url, options);
        this.logger.info(`Navigated to: ${this.page.url()}`);
    }

    async typeIntoField(selector, text, fieldName, delay = 50) {
        if (text === undefined || text === null || String(text).trim() === '') {
            this.logger.debug(`Skipping empty field: ${fieldName} (${selector})`);
            return;
        }
        try {
            await this.page.waitForSelector(selector, { timeout: this.dtConfig.timeouts.mediumWait });
            await this.page.type(selector, String(text), { delay });
            this.logger.debug(`Typed "${String(text).substring(0,50)}" into ${fieldName} (${selector})`);
        } catch (error) {
            this.logger.error(`Error typing into ${fieldName} (${selector}): ${error.message}`);
            throw new Error(`Failed to type into ${fieldName}: ${selector}. Error: ${error.message}`);
        }
    }

    async clickElement(selector, elementName, navigationOptions = null) {
        try {
            await this.page.waitForSelector(selector, { timeout: this.dtConfig.timeouts.mediumWait });
            if (navigationOptions) {
                await Promise.all([
                    this.page.waitForNavigation({ timeout: this.dtConfig.timeouts.navigation, ...navigationOptions }),
                    this.page.click(selector)
                ]);
            } else {
                await this.page.click(selector);
            }
            this.logger.debug(`Clicked ${elementName} (${selector})`);
        } catch (error) {
            this.logger.error(`Error clicking ${elementName} (${selector}): ${error.message}`);
            throw new Error(`Failed to click ${elementName}: ${selector}. Error: ${error.message}`);
        }
    }

    async selectDropdownByValue(selector, value, fieldName) {
        if (value === undefined || value === null || String(value).trim() === '') {
            this.logger.debug(`Skipping empty select field: ${fieldName} (${selector})`);
            return;
        }
        try {
            await this.page.waitForSelector(selector, { timeout: this.dtConfig.timeouts.mediumWait });
            await this.page.select(selector, String(value));
            this.logger.debug(`Selected "${value}" for ${fieldName} (${selector})`);
        } catch (error) {
            this.logger.error(`Error selecting value for ${fieldName} (${selector}): ${error.message}`);
            throw new Error(`Failed to select value for ${fieldName}: ${selector}. Error: ${error.message}`);
        }
    }
    
    async waitForNetworkIdle(timeout = this.dtConfig.timeouts.navigation) {
        try {
            await this.page.waitForNavigation({ waitUntil: 'networkidle0', timeout });
        } catch (e) {
            // Ignore timeout errors as networkidle0 can be tricky
            this.logger.warn(`waitForNetworkIdle timeout or interruption: ${e.message}. Continuing if possible.`);
        }
    }
}
module.exports = BasePage;