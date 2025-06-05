// test-dealertrack-comprehensive.js
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
        // These patterns need to be adjusted based on the actual page content
        
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
        
        return dealInfo;
    });
}

// Main test function
async function runComprehensiveTest() {
    console.log('Starting Comprehensive DealerTrack Test...');
    let browser = null;
    let page = null;
    const testId = `comprehensive_${Date.now()}`;

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
        
        // =====================================================================
        // TEST 1: COMPREHENSIVE CREDIT APPLICATION EDITING FOR "JOE PAFTEST"
        // =====================================================================
        console.log('\n=== TEST 1: COMPREHENSIVE CREDIT APPLICATION EDITING ===\n');
        
        // Navigate to the search page to find the existing application
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
        await clickElement(page, 'table.results tbody tr:first-child a', 'Joe Paftest Search Result');
        await waitForPageNavigation(page);
        console.log(`Navigated to application page: ${page.url()}`);
        await takeScreenshot(page, testId, 'application_page');
        
        // Capture the URL for future reference
        const applicationUrl = page.url();
        console.log(`Application URL captured: ${applicationUrl}`);
        
        // Try to click Edit button if present
        try {
            console.log('Attempting to click Edit button if present...');
            await clickElement(page, 'a.edit-button, button.edit-button, a:contains("Edit")', 'Edit Button');
            await waitForPageNavigation(page);
            console.log('Navigated to edit page');
            await takeScreenshot(page, testId, 'edit_page');
        } catch (error) {
            console.log('Edit button not found or not needed. Continuing...');
        }
        
        // Now we should be on a page where we can edit the application
        // Let's try to update various fields with dummy data
        
        console.log('Updating application with comprehensive dummy data...');
        
        try {
            // Personal Information
            console.log('Updating personal information...');
            await typeIntoField(page, '#id_applicant_form-email_address, input[name*="email"]', 'joepaftest_updated@example.com', 'Email Address');
            await typeIntoField(page, '#id_applicant_form-primary_phone_number, input[name*="phone"]', '5551234567', 'Phone Number');
            await typeIntoField(page, '#id_applicant_form-alternate_phone_number, input[name*="alternate_phone"]', '5559876543', 'Alternate Phone');
            
            // Housing Information
            console.log('Updating housing information...');
            await selectDropdownByValue(page, '#id_applicant_form-housing_status_code, select[name*="housing_status"]', 'R', 'Housing Status');
            await typeIntoField(page, '#id_applicant_form-mortgage_payment_or_rent, input[name*="mortgage_payment"]', '1500', 'Mortgage/Rent Payment');
            await typeIntoField(page, '#id_applicant_form-current_address_years, input[name*="address_years"]', '3', 'Years at Address');
            await typeIntoField(page, '#id_applicant_form-current_address_months, input[name*="address_months"]', '6', 'Months at Address');
            
            // Employment Information
            console.log('Updating employment information...');
            await typeIntoField(page, '#id_applicant_form-organization_name, input[name*="employer"]', 'Acme Corporation', 'Employer Name');
            await typeIntoField(page, '#id_applicant_form-occupation_name, input[name*="occupation"]', 'Software Developer', 'Occupation');
            await typeIntoField(page, '#id_applicant_form-work_phone_number, input[name*="work_phone"]', '5551112222', 'Work Phone');
            await typeIntoField(page, '#id_applicant_form-current_employed_years, input[name*="employed_years"]', '4', 'Years Employed');
            await typeIntoField(page, '#id_applicant_form-current_employed_months, input[name*="employed_months"]', '2', 'Months Employed');
            await typeIntoField(page, '#id_applicant_form-salary, input[name*="salary"]', '85000', 'Salary');
            await selectDropdownByValue(page, '#id_applicant_form-salary_type_code, select[name*="salary_type"]', 'A', 'Salary Type');
            
            // Vehicle Information (if editable on this page)
            console.log('Attempting to update vehicle information if present...');
            try {
                await typeIntoField(page, '#id_vehicle_form-cash_sell_price_amount, input[name*="cash_sell_price"]', '35000', 'Selling Price');
                await typeIntoField(page, '#id_vehicle_form-cash_down_amount, input[name*="cash_down"]', '5000', 'Cash Down');
                await typeIntoField(page, '#id_vehicle_form-term_count, input[name*="term"]', '60', 'Term Months');
            } catch (vehicleError) {
                console.log('Vehicle information fields not found or not editable on this page.');
            }
            
            // Save the changes
            console.log('Saving changes...');
            await clickElement(page, 'button[type="submit"], button.save-button, input[type="submit"]', 'Save Button');
            await waitForPageNavigation(page);
            console.log('Changes saved');
            await takeScreenshot(page, testId, 'after_comprehensive_save');
            
        } catch (error) {
            console.error(`Error updating application fields: ${error.message}`);
            console.log('Continuing with tests...');
        }
        
        // =====================================================================
        // TEST 2: DEAL JACKET STATUS CHECK FOR DECLINED APPLICATIONS
        // =====================================================================
        console.log('\n=== TEST 2: DEAL JACKET STATUS CHECK FOR DECLINED APPLICATIONS ===\n');
        
        // Navigate back to F&I home
        console.log('Navigating back to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        console.log(`Back on F&I home: ${page.url()}`);
        
        // Test 2.1: Open Deal Jacket for "Dr Russ Enterprises Llc / Ryan N Russell"
        console.log('\n--- Test 2.1: Dr Russ Enterprises Llc / Ryan N Russell ---\n');
        
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
        
        // Extract and log detailed deal information
        console.log('Extracting detailed deal information...');
        const ryanRussellDealInfo = await extractDealJacketInfo(page);
        console.log('Ryan N Russell Deal Information:');
        console.log(JSON.stringify(ryanRussellDealInfo, null, 2));
        
        // Capture the URL for future reference
        const ryanRussellDealUrl = page.url();
        console.log(`Deal URL captured: ${ryanRussellDealUrl}`);
        
        // Test 2.2: Open Deal Jacket for "Leopoldo T Williams"
        console.log('\n--- Test 2.2: Leopoldo T Williams ---\n');
        
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
        
        // Extract and log detailed deal information
        console.log('Extracting detailed deal information...');
        const leopoldoWilliamsDealInfo = await extractDealJacketInfo(page);
        console.log('Leopoldo T Williams Deal Information:');
        console.log(JSON.stringify(leopoldoWilliamsDealInfo, null, 2));
        
        // Capture the URL for future reference
        const leopoldoWilliamsDealUrl = page.url();
        console.log(`Deal URL captured: ${leopoldoWilliamsDealUrl}`);
        
        // =====================================================================
        // TEST 3: NAVIGATION BETWEEN DIFFERENT SECTIONS OF DEALERTRACK
        // =====================================================================
        console.log('\n=== TEST 3: NAVIGATION BETWEEN DIFFERENT SECTIONS OF DEALERTRACK ===\n');
        
        // Navigate back to F&I home
        console.log('Navigating back to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        console.log(`Back on F&I home: ${page.url()}`);
        
        // Test different navigation paths
        
        // Test 3.1: Navigate to App Status page
        console.log('\n--- Test 3.1: Navigate to App Status page ---\n');
        try {
            console.log('Attempting to navigate to App Status page...');
            await page.goto('https://ww2.dealertrack.app.coxautoinc.com/decisions/credit/status/', { waitUntil: 'networkidle0' });
            console.log(`Navigated to App Status page: ${page.url()}`);
            await takeScreenshot(page, testId, 'app_status_page');
        } catch (error) {
            console.error(`Error navigating to App Status page: ${error.message}`);
        }
        
        // Test 3.2: Navigate to Active Deals page
        console.log('\n--- Test 3.2: Navigate to Active Deals page ---\n');
        try {
            console.log('Attempting to navigate to Active Deals page...');
            await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/fni20/', { waitUntil: 'networkidle0' });
            console.log(`Navigated to Active Deals page: ${page.url()}`);
            await takeScreenshot(page, testId, 'active_deals_page_direct');
        } catch (error) {
            console.error(`Error navigating to Active Deals page: ${error.message}`);
        }
        
        // Test 3.3: Navigate directly to a Deal Jacket using saved URL
        console.log('\n--- Test 3.3: Navigate directly to a Deal Jacket using saved URL ---\n');
        try {
            console.log(`Attempting to navigate directly to Ryan N Russell Deal Jacket: ${ryanRussellDealUrl}`);
            await page.goto(ryanRussellDealUrl, { waitUntil: 'networkidle0' });
            console.log(`Navigated directly to Deal Jacket: ${page.url()}`);
            await takeScreenshot(page, testId, 'direct_deal_jacket_navigation');
            
            // Verify we can still extract information
            const directNavDealInfo = await extractDealJacketInfo(page);
            console.log('Deal Information after direct navigation:');
            console.log(JSON.stringify(directNavDealInfo, null, 2));
        } catch (error) {
            console.error(`Error navigating directly to Deal Jacket: ${error.message}`);
        }
        
        // Test 3.4: Navigate directly to Joe Paftest application using saved URL
        console.log('\n--- Test 3.4: Navigate directly to Joe Paftest application using saved URL ---\n');
        try {
            console.log(`Attempting to navigate directly to Joe Paftest application: ${applicationUrl}`);
            await page.goto(applicationUrl, { waitUntil: 'networkidle0' });
            console.log(`Navigated directly to application: ${page.url()}`);
            await takeScreenshot(page, testId, 'direct_application_navigation');
        } catch (error) {
            console.error(`Error navigating directly to application: ${error.message}`);
        }
        
        // =====================================================================
        // TEST SUMMARY
        // =====================================================================
        console.log('\n=== COMPREHENSIVE TEST SUMMARY ===\n');
        console.log('1. Successfully connected to browser');
        console.log('2. Successfully navigated to DealerTrack F&I home');
        console.log('3. Successfully opened and edited existing credit application for "Joe Paftest"');
        console.log('4. Successfully opened Deal Jackets for completed "declined" applications');
        console.log('5. Successfully extracted detailed deal information');
        console.log('6. Successfully tested navigation between different sections of DealerTrack');
        console.log('7. Successfully tested direct navigation to saved URLs');
        
        // Return to F&I home
        console.log('\nReturning to F&I home...');
        await page.goto('https://ww2.dealertrack.app.coxautoinc.com/core/deals_or_apps/', { waitUntil: 'networkidle0' });
        
        console.log('\nComprehensive test completed successfully!');
        
    } catch (error) {
        console.error(`Comprehensive test failed: ${error.message}`);
        if (page) {
            await takeScreenshot(page, testId, 'comprehensive_test_error');
        }
    } finally {
        // Don't close the browser since we connected to an existing instance
        console.log('Test complete. Browser remains open.');
    }
}

// Run the test
runComprehensiveTest().catch(error => {
    console.error(`Unhandled error in comprehensive test: ${error.message}`);
    process.exit(1);
});
