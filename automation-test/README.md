# DealerTrack Puppeteer Automation Test

This directory contains test scripts to verify that Puppeteer can successfully interact with DealerTrack's interface. These tests are a proof-of-concept for the Pinnacle Auto Finance Dealer Portal's form automation functionality.

## Overview

The Pinnacle Auto Finance Dealer Portal uses server-side Puppeteer automation to interact with DealerTrack's interface, enabling the complete broker deal lifecycle from dealer registration to deal completion. This test suite verifies that this approach is viable by:

1. Logging into DealerTrack
2. Navigating to the credit application form
3. Filling out form fields
4. Reading information from the page
5. Capturing screenshots at each step

## Files

- `test.js` - Basic test script that demonstrates Puppeteer can interact with DealerTrack
- `advanced-test.js` - Advanced test script that uses field mapping to fill out forms
- `field-mapping.js` - Configuration file that maps our WordPress CPT fields to DealerTrack form fields
- `.env` - Environment variables for DealerTrack credentials and configuration

## Prerequisites

- Node.js (v14 or higher)
- npm (v6 or higher)
- Internet access to connect to DealerTrack

## Setup

1. Install dependencies:

```bash
npm install
```

2. Update the `.env` file with your DealerTrack credentials:

```
DEALERTRACK_USERNAME=your_username
DEALERTRACK_PASSWORD=your_password
```

## Running the Tests

### Basic Test

The basic test demonstrates that Puppeteer can log into DealerTrack and interact with the interface:

```bash
node test.js
```

### Advanced Test

The advanced test uses field mapping to fill out the credit application form with sample data:

```bash
node advanced-test.js
```

## Test Results

The tests will create a `screenshots` directory with screenshots taken at each step of the process. These screenshots can be used to verify that Puppeteer is correctly interacting with DealerTrack's interface.

The test scripts also output detailed logs to the console, including:
- Navigation steps
- Form field interactions
- Page content extraction
- Any errors encountered

## Field Mapping

The `field-mapping.js` file defines the mapping between our WordPress Custom Post Type fields and the corresponding DealerTrack form fields. This mapping is used by the advanced test script to fill out the credit application form.

Each field mapping entry includes:
- The selector for the DealerTrack form field
- The field type (input, select, radio, checkbox)
- An optional transformation function
- Whether the field is required

## Notes

- These tests are designed to run in non-headless mode (`HEADLESS=false` in `.env`) to allow visual verification of the automation.
- The form submission step is commented out to prevent actual submissions to DealerTrack during testing.
- The selectors in `field-mapping.js` are placeholders and will need to be updated based on the actual DealerTrack form structure.
- The test scripts include error handling and fallback mechanisms to handle variations in DealerTrack's interface.

## Next Steps

After verifying that Puppeteer can successfully interact with DealerTrack, the next steps would be:

1. Update the field mapping with the actual DealerTrack form selectors
2. Integrate the automation with the WordPress backend
3. Implement job queuing and processing
4. Add error handling and retry mechanisms
5. Set up monitoring and logging
