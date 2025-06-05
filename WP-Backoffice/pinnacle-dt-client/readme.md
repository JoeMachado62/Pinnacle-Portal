New/Modified File Structure for pinnacle-dt-client:
/pinnacle-dt-client/
├── config/
│   └── dealertrack.js       # New: Centralized DT URLs & selectors
├── node_modules/
├── pages/                     # New: Directory for Page Objects
│   ├── ApplicationWizardPage.js
│   ├── AppStatusPage.js       # For checking status specifically
│   ├── CustomerSearchPage.js
│   ├── DealJacketPage.js      # For interacting with the deal jacket
│   └── DealListPage.js
├── src/
│   ├── browserManager.js      # Minor changes if any
│   ├── config.js              # Loads main config.json
│   ├── dealerTrackAutomator.js # Refactored to use Page Objects
│   ├── jobProcessor.js        # Updated to handle new job payload structure & call refactored automator
│   ├── logger.js
│   └── wordpressClient.js     # Minor changes if any
├── config.example.json
├── config.json                # Main application config
├── index.js                   # Updated to handle new job payload structure
├── package-lock.json
└── package.json
//
Important Notes for dealerTrackAutomator.js:
Selectors are Critical: This code heavily relies on the selectors you've provided from the "consolidated reference" and the HTML source files. If DealerTrack UI changes, these selectors will break.
Mapping DT Values: You'll need to map DealerTrack's specific dropdown values/status texts to the values your WordPress system uses (e.g., appHousing.addressTypeMappedToDT). I've added placeholders like MappedToDT.
Dynamic Content & Waits: Web pages load dynamically. I've used waitForNavigation, waitForSelector, and some crude page.waitForTimeout. Robust automation often requires more sophisticated waiting strategies (e.g., waiting for specific XHR requests to complete, or for specific text to appear).
Error Handling: This includes basic try-catch blocks and screenshotting. More advanced error recovery (e.g., retrying a specific step) could be added.
Previous Address/Employment Logic: The sections marked // TODO: Handle Previous Address/Employment need to implement the conditional logic (based on years/months at current residence/employment) to fill out those sections in DealerTrack, similar to how your credit_application.html JavaScript does it. This means checking if the Puppeteer script needs to input data into those fields.
DealerTrack Reference Number Extraction: The logic to get the dtReferenceNumber after submission is a common tricky part. The current code tries the selector you provided and a URL-based fallback. This often needs fine-tuning once you see the live submission flow.
Status Check Complexity: The checkApplicationStatusInDealerTrack currently uses the "App Status" search page. If you want more detailed data from the "Deal Jacket Summary" page, the navigation would be: F&I Home -> Active Deals -> Find and Click Specific Deal Link (this is the harder part, as you need to identify the correct link in a list based on limited info) -> Scrape Deal Jacket. The "App Status" page is simpler if it provides enough info.