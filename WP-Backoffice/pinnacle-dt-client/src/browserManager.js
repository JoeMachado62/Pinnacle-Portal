// src/browserManager.js
const puppeteer = require('puppeteer-core'); // Use puppeteer-core for connecting
const config = require('./config');
const logger = require('./logger');

let browser = null;
let dealerTrackPage = null; // We might not always need a specific "DealerTrack page" if we navigate directly

async function connectToBrowser() {
    if (browser && browser.isConnected()) {
        logger.info('Already connected to browser.');
        // It's good practice to ensure we can still get pages
        try {
            const pages = await browser.pages();
            if (pages.length === 0) { // Should not happen if connected, but good check
                logger.warn('Browser connected but has no pages. Attempting to create one.');
                await browser.newPage();
            }
        } catch (e) {
            logger.error('Error verifying pages in connected browser. Will attempt to reconnect.', e);
            browser = null; // Force reconnect
        }
    }

    if (!browser || !browser.isConnected()) {
        try {
            logger.info(`Attempting to connect to browser at: ${config.puppeteer.browser_ws_endpoint}`);
            browser = await puppeteer.connect({
                browserURL: config.puppeteer.browser_ws_endpoint,
                defaultViewport: null // Use the browser's current viewport
            });
            logger.info('Successfully connected to existing browser instance.');

            browser.on('disconnected', () => {
                logger.warn('Browser disconnected!');
                browser = null;
                dealerTrackPage = null;
            });

        } catch (error) {
            logger.error(`Failed to connect to browser: ${error.message}`);
            logger.error('Please ensure Chrome/Firefox is running with remote debugging enabled on the correct port (e.g., --remote-debugging-port=9222).');
            throw new Error('Browser connection failed. Ensure Chrome/Firefox is running with remote debugging enabled.');
        }
    }
    return browser;
}

async function getPage() {
    if (!browser || !browser.isConnected()) {
        await connectToBrowser();
    }
    try {
        // Get all open pages
        const pages = await browser.pages();
        let targetPage = null;

        // Try to find an existing DealerTrack page that isn't a PDF viewer or blank
        // This logic might need refinement based on typical browser usage
        for (const page of pages) {
            const url = page.url();
            if (url.includes(config.puppeteer.dealertrack_fni_home_url_substring) && !url.endsWith('.pdf')) {
                 logger.info(`Found existing DealerTrack page: ${url}`);
                 targetPage = page;
                 break;
            }
        }
        
        // If no suitable DealerTrack page is found, use the first available non-blank, non-pdf page,
        // or create a new one if no pages exist or all are unsuitable.
        if (!targetPage) {
            if (pages.length > 0) {
                // Prefer a page that is not 'about:blank' and not a PDF viewer
                targetPage = pages.find(p => p.url() !== 'about:blank' && !p.url().endsWith('.pdf')) || pages[0];
                 logger.info(`Using existing page: ${targetPage.url()}`);
            } else {
                logger.info('No open pages found, creating a new page.');
                targetPage = await browser.newPage();
            }
        }
        
        // Ensure viewport is set if it's null (can happen with new pages)
        if (!targetPage.viewport()) {
            await targetPage.setViewport({ width: 1366, height: 768 }); // A common default
        }

        return targetPage;
    } catch (error) {
        logger.error(`Error getting a page from the browser: ${error.message}`);
        throw error;
    }
}

async function closeBrowser() {
    if (browser && browser.isConnected()) {
        // Since we connected to an existing browser, we typically don't close it.
        // We might disconnect our Puppeteer instance from it.
        logger.info('Disconnecting from browser (not closing it as we connected to an existing instance).');
        await browser.disconnect();
    }
    browser = null;
    dealerTrackPage = null;
}

async function takeScreenshot(page, jobIdentifier, stepName = 'debug') {
    try {
        const screenshotDir = config.puppeteer.screenshot_path || './screenshots';
        const timestamp = new Date().toISOString().replace(/:/g, '-');
        const filename = `${screenshotDir}/error_${jobIdentifier}_${stepName}_${timestamp}.png`;
        await page.screenshot({ path: filename, fullPage: true });
        logger.info(`Screenshot saved to ${filename}`);
        return filename;
    } catch (e) {
        logger.error(`Failed to take screenshot: ${e.message}`);
        return null;
    }
}

// Add this function inside src/browserManager.js
function getBrowserInstanceForHealthCheck() {
    // This is a simplified way. If browser isn't connected, connectToBrowser would throw
    // if it can't connect, which is okay for a health check.
    if (browser && browser.isConnected()) {
        return browser;
    }
    return null; // Or try to connect: await connectToBrowser(); return browser;
}

// Add to module.exports in src/browserManager.js
module.exports = {
    connectToBrowser,
    getPage,
    closeBrowser,
    takeScreenshot,
    getBrowserInstanceForHealthCheck // New export
};

