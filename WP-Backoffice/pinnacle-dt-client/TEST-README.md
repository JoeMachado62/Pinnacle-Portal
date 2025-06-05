# DealerTrack Puppeteer Testing

This directory contains test scripts for testing the Puppeteer component of the Pinnacle DealerTrack Client. These scripts are designed to test the ability to navigate through the DealerTrack portal, open existing credit applications, add dummy data, and check deal statuses.

## Prerequisites

Before running these tests, ensure:

1. You have a Chrome browser open and logged into the DealerTrack platform
2. The Chrome browser has remote debugging enabled (running with `--remote-debugging-port=9222`)
3. The `config.json` file has the correct `browser_ws_endpoint` value (default is `http://localhost:9222`)
4. You are on the DealerTrack F&I home page

## Setting Up Chrome for Testing

For your convenience, we've included scripts to launch Chrome with remote debugging enabled:

### Windows
Run the `launch-chrome-for-testing.bat` script:
```
launch-chrome-for-testing.bat
```

### macOS/Linux
Run the `launch-chrome-for-testing.sh` script:
```
chmod +x launch-chrome-for-testing.sh
./launch-chrome-for-testing.sh
```

After Chrome launches:
1. Log in to DealerTrack
2. Navigate to the F&I home page
3. Keep the Chrome window open while running the tests

## Running the Tests

You can run the tests individually or all at once:

### Running All Tests
For your convenience, we've included scripts to run all tests in sequence:

#### Windows
```
run-all-tests.bat
```

#### macOS/Linux
```
chmod +x run-all-tests.sh
./run-all-tests.sh
```

### Running Individual Tests

#### 1. Basic Test Script (`test-puppeteer.js`)

This script performs basic testing of the Puppeteer component:

- Connects to the existing Chrome browser
- Navigates to the DealerTrack F&I home page
- Opens an existing credit application for "Joe Paftest"
- Adds basic dummy data to the application
- Opens Deal Jackets for the two completed "declined" applications
- Extracts basic deal information

To run this test:

```bash
node test-puppeteer.js
```

### 2. Comprehensive Test Script (`test-dealertrack-comprehensive.js`)

This script performs more comprehensive testing:

- Connects to the existing Chrome browser
- Navigates to the DealerTrack F&I home page
- Opens an existing credit application for "Joe Paftest"
- Adds detailed dummy data to the application
- Opens Deal Jackets for the two completed "declined" applications
- Extracts detailed deal information
- Tests navigation between different sections of DealerTrack
- Tests direct navigation to saved URLs

To run this test:

```bash
node test-dealertrack-comprehensive.js
```

### 3. Declined Applications Test Script (`test-declined-applications.js`)

This script focuses specifically on testing the ability to open and read information from the two declined applications:

- Connects to the existing Chrome browser
- Navigates to the DealerTrack F&I home page
- Opens and reads deal information for "Dr Russ Enterprises Llc / Ryan N Russell"
- Opens and reads deal information for "Leopoldo T Williams"
- Verifies the deal information against expected values (vehicle and status)

To run this test:

```bash
node test-declined-applications.js
```

### 4. Credit Application Test Script (`test-credit-application.js`)

This script focuses specifically on testing the ability to find, open, and edit the "Joe Paftest" credit application:

- Connects to the existing Chrome browser
- Navigates to the DealerTrack F&I home page
- Finds and opens the credit application for "Joe Paftest"
- Edits the application with randomized dummy data
- Verifies the changes were saved correctly

To run this test:

```bash
node test-credit-application.js
```

## Test Output

Both test scripts will:

1. Log detailed information about each step to the console
2. Take screenshots at key points during the test (saved to the `screenshots` directory)
3. Extract and log deal information from Deal Jackets
4. Provide a summary of the test results at the end

## Troubleshooting

If the tests fail, check the following:

1. Ensure Chrome is running with remote debugging enabled
2. Verify you are logged into DealerTrack
3. Check the selectors in the test scripts - they may need adjustment based on the actual DealerTrack UI
4. Review the screenshots in the `screenshots` directory to see where the test failed
5. Check the console output for error messages

## Customizing the Tests

The test scripts use several helper functions that can be reused for custom tests:

- `waitForPageNavigation`: Waits for page navigation to complete
- `typeIntoField`: Types text into a field
- `clickElement`: Clicks on an element
- `selectDropdownByValue`: Selects a value from a dropdown
- `extractDealJacketInfo`: Extracts detailed information from a Deal Jacket

You can modify these functions or create new ones to test specific functionality.

## Notes

- The tests do not close the browser at the end, as they connect to an existing instance
- The tests use selectors that may need adjustment based on the actual DealerTrack UI
- The tests include error handling to continue testing even if some steps fail
- Screenshots are taken at key points to help diagnose issues
