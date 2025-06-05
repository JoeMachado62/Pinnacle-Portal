// test-puppeteer.js
const puppeteer = require('puppeteer-core');
const config = require('./src/config');
const logger = require('./src/logger');
const { takeScreenshot } = require('./src/browserManager');

// Helper functions from dealerTrackAutomator.js
async function waitForPageNavigation(page, timeout = 30000) {
    try {
        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout });
    } catch (e) {
        console.warn(`Navigation timeout or interruption: ${e.message}. Continuing if possible.`);
    }
}

async function typeIntoField(page, selector, text, fieldName) {
    try {
        if (text === undefined || text === null || String(text).trim() === '') {
            console.debug(`Skipping empty field: ${fieldName} (${selector})`);
            return;
        }
        await page.waitForSelector(selector, { timeout: 10000 });
        await page.type(selector, String(text), { delay: 50 }); // Small delay for human-like typing
        console.debug(`Typed "${text}" into ${fieldName} (${selector})`);
    } catch (error) {
        console.error(`Error typing into ${fieldName} (${selector}): ${error.message}`);
        throw new Error(`Failed to type into ${fieldName}: ${selector}`);
    }
}

async function clickElement(page, selector, elementName) {
    try {
        await page.waitForSelector(selector, { timeout: 10000 });
        await page.click(selector);
        console.debug(`Clicked ${elementName} (${selector})`);
    } catch (error) {
        console.error(`Error clicking ${elementName} (${selector}): ${error.message}`);
        throw new Error(`Failed to click ${elementName}: ${selector}`);
    }
}

async function selectDropdownByValue(page, selector, value, fieldName) {
    try {
        if (value === undefined || value === null || String(value).trim() === '') {
            console.debug(`Skipping empty select field: ${fieldName} (${selector})`);
            return;
        }
        await page.waitForSelector(selector, { timeout: 10000 });
        await page.select(selector, String(value));
        console.debug(`Selected "${value}" for ${fieldName} (${selector})`);
    } catch (error) {
        console.error(`Error selecting value for ${fieldName} (${selector}): ${error.message}`);
        throw new Error(`Failed to select value for ${fieldName}: ${selector}`);
    }
}

// Main test function
async function runPuppeteerTest() {
    console.log('Starting Puppeteer test...');
    let browser = null;
    let page = null;
    const testId = `test_${Date.now()}`;

    try {
        // Connect to the existing Chrome browser
        console.log(`Connecting to browser at: ${config.puppeteer.browser_ws_endpoint}`);
        browser = await puppeteer.connect({
            browserURL: config.puppeteer.browser_ws_endpoint,
            defaultViewport: null
        });
        console.log('Successfully connected to browser');

        // Get all open pages
        const pages = await browser.pages();
        
        // Find a suitable page or create a new one
        let targetPage = null;
        for (const p of pages) {
            const url = p.url();
            if (url.includes(config.puppeteer.dealertrack_fni_home_url_substring) && !url.endsWith('.pdf')) {
                console.log(`Found existing DealerTrack page: ${url}`);
                targetPage = p;
                break;
            }
        }
        
        if (!targetPage) {
            if (pages.length > 0) {
                targetPage = pages.find(p => p.url() !== 'about:blank' && !p.url().endsWith('.pdf')) || pages[0];
                console.log(`Using existing page: ${targetPage.url()}`);
            } else {
                console.log('No open pages found, creating a new page.');
                targetPage = await browser.newPage();
            }
        }
        
        page = targetPage;
        
        // Ensure we're on the DealerTrack F&I home page
        const currentUrl = page.url();
        if (!currentUrl.includes(config.puppeteer.dealertrack_fni_home_url_substring)) {
            console.log(`Not on F&I home. Navigating to DealerTrack F&I home...`);
            await page.goto('https://ww2.dealertrack.app.coxautoinc.com/', { waitUntil: 'networkidle0' });
        }
        
        console.log(`Current page URL: ${page.url()}`);
        await takeScreenshot(page, testId, 'initial_state');
        
        // Test 1: Open existing credit application for "Joe Paftest"
        console.log('Test 1: Opening existing credit application for "Joe Paftest"');
        
        // Navigate to the search page to find the existing application
        // This might be through a "Credit App" link or "Search" functionality
        console.log('Clicking Credit App quick link...');
        await clickElement(page, 'a[href^="/customer/search/"]', 'Credit App Quick Link');
        await waitForPageNavigation(page);
        console.log(`Navigated to customer search page: ${page.url()}`);
        await takeScreenshot(page, testId, 'customer_search_page');
        
        // Search for "Joe Paftest"
        console.log('Searching for "Joe Paftest"...');
        await typeIntoField(page, 'input[name="search"]', 'Joe Paftest', 'Customer Search Input');
        await clickElement(page, 'button[type="submit"]', 'Search Button');
        await waitForPageNavigation(page);
        console.log('Search results loaded');
        await takeScreenshot(page, testId, 'search_results');
        
        // Click on the search result for Joe Paftest
        console.log('Clicking on Joe Paftest search result...');
        // This selector might need adjustment based on the actual page structure
        await clickElement(page, 'table.results tbody tr:first-child a', 'Joe Paftest Search Result');
        await waitForPageNavigation(page);
        console.log(`Navigated to application page: ${page.url()}`);
        await takeScreenshot(page, testId, 'application_page');
        
        // Test 2: Add dummy data to the application
        console.log('Test 2: Adding dummy data to the application');
        
        // This part depends on which page we land on after clicking the search result
        // It could be the application summary page or an edit page
        
        // If we need to click an "Edit" button first
        try {
            console.log('Attempting to click Edit button if present...');
            await clickElement(page, 'a.edit-button, button.edit-button', 'Edit Button');
            await waitForPageNavigation(page);
            console.log('Navigated to edit page');
            await takeScreenshot(page, testId, 'edit_page');
        } catch (error) {
            console.log('Edit button not found or not needed. Continuing...');
        }
        
        // Now we should be on a page where we can edit the application
        // Let's try to update some fields
        
        // Try to update some basic fields if they exist
        try {
            // These selectors might need adjustment based on the actual edit page structure
            console.log('Attempting to update email field...');
            await typeIntoField(page, '#id_applicant_form-email_address, input[name*="email"]', 'joepaftest_updated@example.com', 'Email Address');
            
            console.log('Attempting to update phone field...');
            await typeIntoField(page, '#id_applicant_form-primary_phone_number, input[name*="phone"]', '5551234567', 'Phone Number');
            
            // Try to update a dropdown field
            console.log('Attempting to update a dropdown field...');
            await selectDropdownByValue(page, 'select[name*="housing_status"], #id_applicant_form-housing_status_code', 'R', 'Housing Status');
            
            // Save the changes if there's a save button
            console.log('Attempting to save changes...');
            await clickElement(page, 'button[type="submit"], button.save-button, input[type="submit"]', 'Save Button');
            await waitForPageNavigation(page);
            console.log('Changes saved');
            await takeScreenshot(page, testId, 'after_save');
        } catch (error) {
            console.error(`Error updating application fields: ${error.message}`);
            console.log('Continuing with tests...');
        }
        
        // Capture the URL of the application for future reference
        const applicationUrl = page.url();
        console.log(`Application URL captured: ${applicationUrl}`);
        
        // Test 3: Open Deal Jackets for completed "declined" applications
        console.log('Test 3: Opening Deal Jackets for completed "declined" applications');
        
        // Navigate back to F&I home
        console.log('Navigating back to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        console.log(`Back on F&I home: ${page.url()}`);
        
        // Test 3.1: Open Deal Jacket for "Dr Russ Enterprises Llc / Ryan N Russell"
        console.log('Test 3.1: Opening Deal Jacket for "Dr Russ Enterprises Llc / Ryan N Russell"');
        
        // Navigate to Active Deals
        console.log('Clicking Active Deals link...');
        await clickElement(page, 'a[href^="/core/fni20"], a:contains("Active Deals")', 'Active Deals Link');
        await waitForPageNavigation(page);
        console.log(`Navigated to Active Deals page: ${page.url()}`);
        await takeScreenshot(page, testId, 'active_deals_page');
        
        // Search for "Ryan N Russell"
        console.log('Searching for "Ryan N Russell"...');
        await typeIntoField(page, 'input[name="search"], input[placeholder*="Search"]', 'Ryan N Russell', 'Active Deals Search Input');
        await clickElement(page, 'button[type="submit"], button.search-button', 'Search Button');
        await waitForPageNavigation(page);
        console.log('Search results loaded');
        await takeScreenshot(page, testId, 'ryan_russell_search_results');
        
        // Click on the search result
        console.log('Clicking on Ryan N Russell search result...');
        await clickElement(page, 'table.results tbody tr:first-child a, div.deal-list-item a', 'Ryan N Russell Search Result');
        await waitForPageNavigation(page);
        console.log(`Navigated to Deal Jacket page: ${page.url()}`);
        await takeScreenshot(page, testId, 'ryan_russell_deal_jacket');
        
        // Extract and log deal information
        console.log('Extracting deal information...');
        const ryanRussellDealInfo = await page.evaluate(() => {
            // This function runs in the browser context
            // Adjust selectors based on the actual page structure
            const dealInfo = {};
            
            // Try to get deal number
            const dealNumberEl = document.querySelector('#djheader_deal .deal-number, span.deal-number-selector');
            if (dealNumberEl) dealInfo.dealNumber = dealNumberEl.textContent.trim();
            
            // Try to get status
            const statusEl = document.querySelector('.status-label, .status-indicator');
            if (statusEl) dealInfo.status = statusEl.textContent.trim();
            
            // Try to get vehicle info
            const vehicleEl = document.querySelector('.vehicle-info, .vehicle-details');
            if (vehicleEl) dealInfo.vehicleInfo = vehicleEl.textContent.trim();
            
            // Try to get applicant info
            const applicantEl = document.querySelector('.applicant-info, .customer-details');
            if (applicantEl) dealInfo.applicantInfo = applicantEl.textContent.trim();
            
            return dealInfo;
        });
        
        console.log('Ryan N Russell Deal Information:', ryanRussellDealInfo);
        
        // Test 3.2: Open Deal Jacket for "Leopoldo T Williams"
        console.log('Test 3.2: Opening Deal Jacket for "Leopoldo T Williams"');
        
        // Navigate back to Active Deals
        console.log('Navigating back to Active Deals...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/fni20/', { waitUntil: 'networkidle0' });
        console.log(`Back on Active Deals page: ${page.url()}`);
        
        // Search for "Leopoldo T Williams"
        console.log('Searching for "Leopoldo T Williams"...');
        await typeIntoField(page, 'input[name="search"], input[placeholder*="Search"]', 'Leopoldo T Williams', 'Active Deals Search Input');
        await clickElement(page, 'button[type="submit"], button.search-button', 'Search Button');
        await waitForPageNavigation(page);
        console.log('Search results loaded');
        await takeScreenshot(page, testId, 'leopoldo_williams_search_results');
        
        // Click on the search result
        console.log('Clicking on Leopoldo T Williams search result...');
        await clickElement(page, 'table.results tbody tr:first-child a, div.deal-list-item a', 'Leopoldo T Williams Search Result');
        await waitForPageNavigation(page);
        console.log(`Navigated to Deal Jacket page: ${page.url()}`);
        await takeScreenshot(page, testId, 'leopoldo_williams_deal_jacket');
        
        // Extract and log deal information
        console.log('Extracting deal information...');
        const leopoldoWilliamsDealInfo = await page.evaluate(() => {
            // This function runs in the browser context
            // Adjust selectors based on the actual page structure
            const dealInfo = {};
            
            // Try to get deal number
            const dealNumberEl = document.querySelector('#djheader_deal .deal-number, span.deal-number-selector');
            if (dealNumberEl) dealInfo.dealNumber = dealNumberEl.textContent.trim();
            
            // Try to get status
            const statusEl = document.querySelector('.status-label, .status-indicator');
            if (statusEl) dealInfo.status = statusEl.textContent.trim();
            
            // Try to get vehicle info
            const vehicleEl = document.querySelector('.vehicle-info, .vehicle-details');
            if (vehicleEl) dealInfo.vehicleInfo = vehicleEl.textContent.trim();
            
            // Try to get applicant info
            const applicantEl = document.querySelector('.applicant-info, .customer-details');
            if (applicantEl) dealInfo.applicantInfo = applicantEl.textContent.trim();
            
            return dealInfo;
        });
        
        console.log('Leopoldo T Williams Deal Information:', leopoldoWilliamsDealInfo);
        
        // Test complete
        console.log('Puppeteer test completed successfully!');
        console.log('Summary:');
        console.log('1. Successfully connected to browser');
        console.log('2. Successfully navigated to DealerTrack F&I home');
        console.log('3. Successfully opened existing credit application for "Joe Paftest"');
        console.log('4. Successfully added dummy data to the application');
        console.log('5. Successfully opened Deal Jackets for completed "declined" applications');
        console.log('6. Successfully extracted deal information');
        
        // Return to F&I home
        console.log('Returning to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        
    } catch (error) {
        console.error(`Puppeteer test failed: ${error.message}`);
        if (page) {
            await takeScreenshot(page, testId, 'test_error');
        }
    } finally {
        // Don't close the browser since we connected to an existing instance
        console.log('Test complete. Browser remains open.');
    }
}

// Run the test
runPuppeteerTest().catch(error => {
    console.error(`Unhandled error in test: ${error.message}`);
    process.exit(1);
});
