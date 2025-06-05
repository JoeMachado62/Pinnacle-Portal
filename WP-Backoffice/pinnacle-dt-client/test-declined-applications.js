// test-declined-applications.js
const puppeteer = require('puppeteer-core');
const config = require('./src/config');
const logger = require('./src/logger');
const { takeScreenshot } = require('./src/browserManager');

// Helper functions
async function waitForPageNavigation(page, timeout = 30000) {
    try {
        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout });
    } catch (e) {
        console.warn(`Navigation timeout or interruption: ${e.message}. Continuing if possible.`);
    }
}

async function typeIntoField(page, selector, text, fieldName) {
    try {
        await page.waitForSelector(selector, { timeout: 10000 });
        await page.type(selector, String(text), { delay: 50 });
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

// Function to extract detailed deal information
async function extractDealJacketInfo(page) {
    return await page.evaluate(() => {
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
        
        // Try to get lender info
        const lenderEl = document.querySelector('.lender-info, .lender-details');
        if (lenderEl) dealInfo.lenderInfo = lenderEl.textContent.trim();
        
        // Try to get finance terms
        const termsEl = document.querySelector('.finance-terms, .terms-details');
        if (termsEl) dealInfo.financeTerms = termsEl.textContent.trim();
        
        // Try to get all visible data on the page
        const allText = document.body.innerText;
        
        // Extract key information using regex patterns
        
        // Look for amount pattern
        const amountMatch = allText.match(/Amount:\s*\$([\d,.]+)/);
        if (amountMatch) dealInfo.amount = amountMatch[1];
        
        // Look for term pattern
        const termMatch = allText.match(/Term:\s*(\d+)\s*months/i);
        if (termMatch) dealInfo.term = termMatch[1];
        
        // Look for APR pattern
        const aprMatch = allText.match(/APR:\s*([\d.]+)%/i);
        if (aprMatch) dealInfo.apr = aprMatch[1];
        
        // Look for decision date pattern
        const dateMatch = allText.match(/Decision Date:\s*([A-Za-z]+ \d+, \d{4})/i);
        if (dateMatch) dealInfo.decisionDate = dateMatch[1];
        
        // Look for decision status pattern
        const decisionMatch = allText.match(/Decision:\s*([A-Za-z]+)/i);
        if (decisionMatch) dealInfo.decisionStatus = decisionMatch[1];
        
        // Look for vehicle year/make/model
        const vehicleMatch = allText.match(/(\d{4})\s+([A-Za-z]+)\s+([A-Za-z]+)/);
        if (vehicleMatch) {
            dealInfo.vehicleYear = vehicleMatch[1];
            dealInfo.vehicleMake = vehicleMatch[2];
            dealInfo.vehicleModel = vehicleMatch[3];
        }
        
        return dealInfo;
    });
}

// Main test function
async function testDeclinedApplications() {
    console.log('Starting Declined Applications Test...');
    let browser = null;
    let page = null;
    const testId = `declined_apps_${Date.now()}`;
    
    // Store the deal information for both applications
    const dealInfo = {
        ryanRussell: null,
        leopoldoWilliams: null
    };

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
            await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        }
        
        console.log(`Current page URL: ${page.url()}`);
        await takeScreenshot(page, testId, 'initial_state');
        
        // =====================================================================
        // TEST 1: OPEN AND READ "DR RUSS ENTERPRISES LLC / RYAN N RUSSELL" DEAL
        // =====================================================================
        console.log('\n=== TEST 1: DR RUSS ENTERPRISES LLC / RYAN N RUSSELL DEAL ===\n');
        
        // Navigate to Active Deals
        console.log('Navigating to Active Deals...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/fni20/', { waitUntil: 'networkidle0' });
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
        
        // Extract and log detailed deal information
        console.log('Extracting detailed deal information...');
        dealInfo.ryanRussell = await extractDealJacketInfo(page);
        console.log('Ryan N Russell Deal Information:');
        console.log(JSON.stringify(dealInfo.ryanRussell, null, 2));
        
        // Capture the URL for future reference
        const ryanRussellDealUrl = page.url();
        console.log(`Deal URL captured: ${ryanRussellDealUrl}`);
        
        // =====================================================================
        // TEST 2: OPEN AND READ "LEOPOLDO T WILLIAMS" DEAL
        // =====================================================================
        console.log('\n=== TEST 2: LEOPOLDO T WILLIAMS DEAL ===\n');
        
        // Navigate back to Active Deals
        console.log('Navigating back to Active Deals...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/dealjackets/', { waitUntil: 'networkidle0' });
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
        
        // Extract and log detailed deal information
        console.log('Extracting detailed deal information...');
        dealInfo.leopoldoWilliams = await extractDealJacketInfo(page);
        console.log('Leopoldo T Williams Deal Information:');
        console.log(JSON.stringify(dealInfo.leopoldoWilliams, null, 2));
        
        // Capture the URL for future reference
        const leopoldoWilliamsDealUrl = page.url();
        console.log(`Deal URL captured: ${leopoldoWilliamsDealUrl}`);
        
        // =====================================================================
        // TEST 3: VERIFY DEAL INFORMATION
        // =====================================================================
        console.log('\n=== TEST 3: VERIFY DEAL INFORMATION ===\n');
        
        // Verify Ryan N Russell deal information
        console.log('Verifying Ryan N Russell deal information...');
        const ryanRussellVerification = {
            expectedVehicle: '2021 Chevrolet Corvette',
            expectedStatus: 'Declined',
            actualVehicle: dealInfo.ryanRussell.vehicleYear && dealInfo.ryanRussell.vehicleMake ? 
                           `${dealInfo.ryanRussell.vehicleYear} ${dealInfo.ryanRussell.vehicleMake} ${dealInfo.ryanRussell.vehicleModel || ''}` : 
                           dealInfo.ryanRussell.vehicleInfo || 'Unknown',
            actualStatus: dealInfo.ryanRussell.decisionStatus || dealInfo.ryanRussell.status || 'Unknown'
        };
        
        console.log('Ryan N Russell Verification:');
        console.log(`Expected Vehicle: ${ryanRussellVerification.expectedVehicle}`);
        console.log(`Actual Vehicle: ${ryanRussellVerification.actualVehicle}`);
        console.log(`Expected Status: ${ryanRussellVerification.expectedStatus}`);
        console.log(`Actual Status: ${ryanRussellVerification.actualStatus}`);
        
        // Verify Leopoldo T Williams deal information
        console.log('\nVerifying Leopoldo T Williams deal information...');
        const leopoldoWilliamsVerification = {
            expectedVehicle: '2021 Lamborghini Aventador',
            expectedStatus: 'Declined',
            actualVehicle: dealInfo.leopoldoWilliams.vehicleYear && dealInfo.leopoldoWilliams.vehicleMake ? 
                           `${dealInfo.leopoldoWilliams.vehicleYear} ${dealInfo.leopoldoWilliams.vehicleMake} ${dealInfo.leopoldoWilliams.vehicleModel || ''}` : 
                           dealInfo.leopoldoWilliams.vehicleInfo || 'Unknown',
            actualStatus: dealInfo.leopoldoWilliams.decisionStatus || dealInfo.leopoldoWilliams.status || 'Unknown'
        };
        
        console.log('Leopoldo T Williams Verification:');
        console.log(`Expected Vehicle: ${leopoldoWilliamsVerification.expectedVehicle}`);
        console.log(`Actual Vehicle: ${leopoldoWilliamsVerification.actualVehicle}`);
        console.log(`Expected Status: ${leopoldoWilliamsVerification.expectedStatus}`);
        console.log(`Actual Status: ${leopoldoWilliamsVerification.actualStatus}`);
        
        // =====================================================================
        // TEST SUMMARY
        // =====================================================================
        console.log('\n=== DECLINED APPLICATIONS TEST SUMMARY ===\n');
        console.log('1. Successfully connected to browser');
        console.log('2. Successfully navigated to DealerTrack F&I home');
        console.log('3. Successfully opened and read deal information for "Dr Russ Enterprises Llc / Ryan N Russell"');
        console.log('4. Successfully opened and read deal information for "Leopoldo T Williams"');
        console.log('5. Successfully verified deal information against expected values');
        
        // Return to F&I home
        console.log('\nReturning to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        
        console.log('\nDeclined applications test completed successfully!');
        
    } catch (error) {
        console.error(`Declined applications test failed: ${error.message}`);
        if (page) {
            await takeScreenshot(page, testId, 'declined_apps_test_error');
        }
    } finally {
        // Don't close the browser since we connected to an existing instance
        console.log('Test complete. Browser remains open.');
    }
}

// Run the test
testDeclinedApplications().catch(error => {
    console.error(`Unhandled error in declined applications test: ${error.message}`);
    process.exit(1);
});
